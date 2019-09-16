<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Event;

use LessonBundle\Entity\Lesson;
use Symfony\Component\EventDispatcher\Event;
use UserBundle\Entity\AbstractUser;

/**
 * Description of LessonEvent
 *
 */
class LessonEvent extends Event
{
    /**
     * @var AbstractUser 
     */
    protected $author;
    
    /**
     * @var Lesson 
     */
    protected $lesson;
    
    public function __construct(Lesson $lesson, AbstractUser $author)
    {
        $this->lesson = $lesson;
        $this->author = $author;
    }
    
    public function getAuthor()
    {
        return $this->author;
    }
    
    public function getLesson()
    {
        return $this->lesson;
    }
}