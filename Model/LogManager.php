<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Model;

use AppBundle\Model\BaseManager;
use LessonBundle\Entity\Log;
use LessonBundle\Enum\LogType;

/**
 * Description of LogManager
 *
 */
class LogManager extends BaseManager
{
    protected $class = Log::class;
    
    public function createEntry($type)
    {
        if(!LogType::isValidValue($type)) {
            throw LogType::getValueError($type);
        }
        
        $entry = new Log();
        $entry->setType($type);
        
        return $entry;
    }
}
