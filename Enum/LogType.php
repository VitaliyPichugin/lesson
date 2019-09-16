<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Enum;

use AppBundle\Enum\BaseEnum;

/**
 * Description of LogTypeEnum
 *
 */
class LogType extends BaseEnum
{
    const TYPE_INITIALIZED = 0;
    const TYPE_TEACHER_ENTERED = 1;
    const TYPE_STUDENT_ENTERED = 2;
    const TYPE_TEACHER_FINISHED = 3;
    const TYPE_STUDENT_FINISHED = 4;
}
