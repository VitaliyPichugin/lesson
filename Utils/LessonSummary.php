<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Utils;

/**
 * Description of LessonSummary
 *
 */
class LessonSummary
{
    public $amount;
    public $lessons;
    
    public function __construct()
    {
        $this->amount = '0.0000';
        $this->lessons = 0;
    }
}
