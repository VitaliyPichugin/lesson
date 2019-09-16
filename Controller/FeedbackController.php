<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Controller;

use AppBundle\Controller\JsonController;
use LessonBundle\Form\FeedbackType;
use LessonBundle\Model\FeedbackManager;
use LessonBundle\Security\LessonFeedbackVoterActions;
use LessonBundle\Security\LessonVoterActions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of FeedbackController
 *
 */
class FeedbackController extends JsonController
{
    /**
     * @var FeedbackManager
     */
    private $feedbackManager;
    
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->feedbackManager = $this->container->get('lesson.feedback_manager');
        $this->lessonManager = $this->container->get('lesson.lesson_manager');
    }
    
    public function viewAction($lessonId)
    {
        $lesson = $this->lessonManager->find($lessonId);
        $user = $this->getUser();
        
        if(!$lesson) {
            throw $this->createNotFoundException('lesson.not_found');
        }
        
        if(!$this->isGranted(LessonFeedbackVoterActions::VIEW, $lesson->getFeedback())) {
            throw $this->createNotFoundException('lesson.feedback.not_found');
        }
        
        return $this->render('LessonBundle:Feedback:view.html.twig', [
            'feedback' => $lesson->getFeedback(),
        ]);
    }
    
    public function createAction(Request $request, $lessonId)
    {
        $lesson = $this->lessonManager->find($lessonId);
        $user = $this->getUser();
        
        if(!$lesson || !$lesson->getPurchase()->getStudent()->isEqualTo($user)) {
            throw $this->createNotFoundException('lesson.not_found');
        }
        
        if(!$this->isGranted(LessonVoterActions::FEEDBACK_CREATE, $lesson)) {
            $this->addFlash('error', 'lesson.feedback.not_finished');

            return $this->redirectToRoute('user.dashboard.current');
         }
        
        $feedback = $this->feedbackManager->create();
        $feedback->setLesson($lesson);
        
        $form = $this->createForm(FeedbackType::class, $feedback, [
            'languages' => [],
        ]);
        
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $newFeedback = $form->getData();

            $this->feedbackManager->update($newFeedback);
            $this->addFlash('success', 'lesson.feedback.submitted');

            // ADD flash message about successful submission of feedback
            return $this->redirectToRoute('teacher.profile.view', [
                'username' => $lesson->getPurchase()->getTeacher()->getUsername(),
            ]);
        }
        
        return $this->render(':Lesson:feedback.html.twig', [
            'form' => $form->createView(),
            'lesson' => $lesson,
        ]);
    }
}
