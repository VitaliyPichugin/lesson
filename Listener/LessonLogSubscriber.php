<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Listener;

use LessonBundle\Entity\Lesson;
use LessonBundle\Enum\LogType;
use LessonBundle\Event\LessonEvent;
use LessonBundle\Event\LessonEvents;
use LessonBundle\Model\LogManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Description of LessonLogListener
 *
 */
class LessonLogSubscriber implements EventSubscriberInterface
{
    /**
     * @var LogManager 
     */
    private $logManager;
    
    public function __construct(LogManager $logManager)
    {
        $this->logManager = $logManager;
    }
    
    public static function getSubscribedEvents()
    {
        return [
            LessonEvents::CLASSROOM_INITIALIZED => 'onClassInitialize',
            LessonEvents::CLASSROOM_ENTERED => 'onClassEnter',
            LessonEvents::FINISHED => 'onFinished'
        ];
    }
        
    public function onClassInitialize(LessonEvent $event)
    {        
        $type = LogType::TYPE_INITIALIZED;
        $lesson = $event->getLesson();
        $this->createLogEntry($type, $lesson);
    }
    
    public function onClassEnter(LessonEvent $event)
    {
        $author = $event->getAuthor();
        $lesson = $event->getLesson();
        
        if($author->isEqualTo($lesson->getPurchase()->getTeacher())) {
            $type = LogType::TYPE_TEACHER_ENTERED;
        } else {
            $type = LogType::TYPE_STUDENT_ENTERED;
        }
        
        $this->createLogEntry($type, $lesson);
    }
    
    public function onFinished(LessonEvent $event)
    {
        $author = $event->getAuthor();
        $lesson = $event->getLesson();
        
        if($author->isEqualTo($lesson->getPurchase()->getTeacher())) {
            $type = LogType::TYPE_TEACHER_FINISHED;
        } else {
            $type = LogType::TYPE_STUDENT_FINISHED;
        }
        
        $this->createLogEntry($type, $lesson);        
    }
    
    private function createLogEntry($type, Lesson $lesson)
    {
        $entry = $this->logManager->createEntry($type);
        $entry->setLesson($lesson);
        $this->logManager->update($entry);
    }
}
