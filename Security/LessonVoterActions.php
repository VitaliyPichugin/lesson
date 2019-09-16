<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Security;

use AppBundle\Enum\BaseEnum;

/**
 * Description of LessonVoterActions
 *
 */
class LessonVoterActions extends BaseEnum
{
    const ENTER = 'enter';
    const CANCEL = 'cancel';
    const INITIALIZE = 'initialize';
    const EDIT = 'edit';
    const RATE = 'rate';
    const MANAGE_RESOURCES = 'resources.manage';
    const RESOURCES_MANAGE = self::MANAGE_RESOURCES;
    const FINISH = 'finish';
    const FEEDBACK_CREATE = 'feedback.create';
    const CALL = 'call';
}
