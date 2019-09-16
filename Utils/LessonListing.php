<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Utils;

use ArrayIterator;
use Carbon\Carbon;
use IteratorAggregate;

/**
 * Description of LessonListing
 *
 */
class LessonListing implements IteratorAggregate
{
    private $lessons;
    
    private $lessonsByDate;
    
    public function __construct(array $lessons)
    {
        $this->lessons = $lessons;
    }

    public function getIterator()
    {
        if(!$this->lessonsByDate) {
            $aggregate = [];
            
            foreach($this->lessons as $lesson) {
                $date = Carbon::instance($lesson->getStartDate())->toDateString();
                
                if(!isset($aggregate[$date])) {
                    $aggregate[$date] = [];
                }
                
                $aggregate[$date][] = $lesson;
            }
            
            $this->lessonsByDate = $aggregate;
        }
        
        return new ArrayIterator($this->lessonsByDate);
    }

}
