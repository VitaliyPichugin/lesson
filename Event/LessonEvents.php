<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Event;

/**
 * Description of LessonEvents
 *
 */
class LessonEvents
{
    const CANCELLED = 'lesson.cancelled';
    const CLASSROOM_INITIALIZED = 'lesson.classroom.initialized';
    const CLASSROOM_ENTERED = 'lesson.classroom.entered';
    const FINISHED = 'lesson.finished';
    const BOOKED = 'lesson.booked';
}
