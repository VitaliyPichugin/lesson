<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Event;

use LessonBundle\Entity\Feedback;
use Symfony\Component\EventDispatcher\Event;

/**
 * Description of FeedbackEvent
 *
 */
class FeedbackEvent extends Event
{
    /**
     * @var Feedback 
     */
    private $feedback;
    
    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
    }
    
    public function getFeedback()
    {
        return $this->feedback;
    }
}
