<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Listener;

use LessonBundle\Event\FeedbackEvent;
use LessonBundle\Event\FeedbackEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Description of FeedbackSubscriber
 *
 */
class FeedbackSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            FeedbackEvents::CREATED => [
                'onCreated',
            ],
        ];
    }

    public function onCreated(FeedbackEvent $event)
    {
        $feedback = $event->getFeedback();
        $feedbackRate = $feedback->getAverageRating();
        
        if($feedbackRate > 2.5) {
            // CREATE STUDENT -> TEACHER TRANSFER
        }
    }
}
