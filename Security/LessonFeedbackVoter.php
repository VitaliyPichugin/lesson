<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Security;

use LessonBundle\Entity\Feedback;
use LessonBundle\Entity\Lesson;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Serializer\Exception\LogicException;
use UserBundle\Entity\AbstractUser;

/**
 * Description of LessonVoter
 *
 */
class LessonFeedbackVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        if(!in_array($attribute, LessonFeedbackVoterActions::getConstValues())) {
            return false;
        }
        
        if(!$subject instanceof Feedback) {
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
            case LessonFeedbackVoterActions::VIEW:
                return $this->canView($lesson, $user);
        }
        
        throw new LogicException('This code should not be reached!');
    }
    
    private function canView(Feedback $feedback, AbstractUser $user)
    {
        if(!$this->feedbackBelongsToUser($feedback, $user)) {
            return false;
        }
        
        return true;
    }
    
    private function feedbackBelongsToUser(Feedback $feedback, $user)
    {
        $lessonPurchase = $feedback->getLesson()->getPurchase();
        if(!$lessonPurchase->getTeacher()->isEqualTo($user) && !$lessonPurchase->getStudent()->isEqualTo($user)) {
            return false;
        }
        
        return true;
    }
}
