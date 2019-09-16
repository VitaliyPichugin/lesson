<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Security;

use Carbon\Carbon;
use LessonBundle\Entity\Lesson;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UserBundle\Entity\AbstractUser;
use UserBundle\Entity\StudentUser;
use UserBundle\Entity\TeacherUser;

/**
 * Description of LessonVoter
 *
 */
class LessonVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        if(!in_array($attribute, LessonVoterActions::getConstValues())) {
            return false;
        }
        
        if(!$subject instanceof Lesson) {
            return false;
        }
        
        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof AbstractUser) {
            return false;
        }
        
        /* @var $lesson Lesson */
        $lesson = $subject;
        
        switch($attribute) {
            case LessonVoterActions::ENTER:
                return $this->canEnter($lesson, $user);
            case LessonVoterActions::CANCEL:
                return $this->canCancel($lesson, $user);
            case LessonVoterActions::INITIALIZE:
                return $this->canInitialize($lesson, $user);
            case LessonVoterActions::EDIT:
                return $this->canChangeContent($lesson, $user);
            case LessonVoterActions::FEEDBACK_CREATE:
                return $this->canRate($lesson, $user);
            case LessonVoterActions::RESOURCES_MANAGE:
                return $this->canManageResources($lesson, $user);
            case LessonVoterActions::FINISH:
                return $this->canFinish($lesson, $user);
            case LessonVoterActions::CALL:
                return $this->canCall($lesson, $user);
        }
        
        throw new \LogicException('This code should not be reached!');
    }

    private function canEnter(Lesson $lesson, AbstractUser $user)
    {
        if($user->isEqualTo($lesson->getPurchase()->getStudent())) {
            if(in_array($lesson->getStatus(), [Lesson::STATUS_CREATED, Lesson::STATUS_CANCELLED, Lesson::STATUS_REFUNDED])) {
                return false;
            }
            
            if(null === $lesson->getOpentokSessionId()) {
                return false;
            }
            
            return true;
        }
        
        if($user->isEqualTo($lesson->getPurchase()->getTeacher())) {
            if(in_array($lesson->getStatus(), [Lesson::STATUS_CANCELLED, Lesson::STATUS_REFUNDED])) {
                return false;
            }
            
            if(null === $lesson->getOpentokSessionId() && $lesson->getStatus() !== Lesson::STATUS_CREATED) {
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    private function canCancel(Lesson $lesson, AbstractUser $user) {
        $invalidStatuses = [
            Lesson::STATUS_REFUNDED, 
            Lesson::STATUS_CANCELLED, 
            Lesson::STATUS_FINISHED];
        
        if(in_array($lesson->getStatus(), $invalidStatuses)) {
                return false;
        }
        
        if($user->isEqualTo($lesson->getPurchase()->getStudent())) {
            $lessonDt = Carbon::instance($lesson->getStartDate());

            if($lessonDt->diffInHours(null, false) > -24) {
                return false;
            }

            return true;
        }
        
        if($lesson->getPurchase()->getTeacher()->isEqualTo($user)) {
            return true;
        }
            
        return false;
    }
    
    private function canInitialize(Lesson $lesson, AbstractUser $user)
    {
        if($lesson->getStatus() !== Lesson::STATUS_CREATED) {
            return false;
        }
        
        if(!$user instanceof TeacherUser) {
            return false;
        }
        
        if(!$user->isEqualTo($lesson->getPurchase()->getTeacher())) {
            return false;
        }
        
        return true;
    }
    
    private function canChangeContent(Lesson $lesson, AbstractUser $user)
    {
        if($lesson->getStatus() !== Lesson::STATUS_INITIALIZED) {
            return false;
        }
        
        if(!$this->classBelongsToUser($lesson, $user)) {
            return false;
        }
        
        return true;
    }
    
    private function canRate(Lesson $lesson, AbstractUser $user)
    {
        if($lesson->getStatus() !== Lesson::STATUS_FINISHED) {
            return false;
        }
        
        if(!$lesson->getPurchase()->getStudent()->isEqualTo($user)) {
            return false;
        }
        
        if($lesson->getFeedback()) {
            return false;
        }
        
        return true;
    }
    
    private function canManageResources(Lesson $lesson, AbstractUser $user)
    {
        if($lesson->getStatus() === Lesson::STATUS_CANCELLED || $lesson->getStatus() === Lesson::STATUS_REFUNDED) {
            return false;
        }
        
        if(!$lesson->getPurchase()->getTeacher()->isEqualTo($user)) {
            return false;
        }
        
        return true;
    }
    
    private function canFinish(Lesson $lesson, AbstractUser $user)
    {
        $validStatuses = [Lesson::STATUS_CREATED, Lesson::STATUS_INITIALIZED];
        
        if(!in_array($lesson->getStatus(), $validStatuses)) {
            return false;
        }
        
        if(!$this->classBelongsToUser($lesson, $user)) {
            return false;
        }
                
        return true;
    }
    
    private function canCall(Lesson $lesson, AbstractUser $user)
    {
        if($lesson->getStatus() !== Lesson::STATUS_INITIALIZED) {
            return false;
        }
        
        if(!$lesson->getPurchase()->getTeacher()->isEqualTo($user)) {
            return false;
        }
        
        return true;
    }
    
    private function classBelongsToUser(Lesson $lesson, AbstractUser $user)
    {
        if($lesson->getPurchase()->getTeacher()->isEqualTo($user)) {
            return true;
        }
        
        if($lesson->getPurchase()->getStudent()->isEqualTo($user)) {
            return true;
        }
        
        return false;
    }
}
