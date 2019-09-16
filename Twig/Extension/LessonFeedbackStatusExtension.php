<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Twig\Extension;

use LessonBundle\Entity\Feedback;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Description of LessonFeedbackStatusExtension
 *
 */
class LessonFeedbackStatusExtension extends Twig_Extension implements ContainerAwareInterface
{    
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('feedback_status', [$this, 'feedbackStatus']),
            new \Twig_SimpleFilter('feedback_status_label', [$this, 'feedbackStatusLabel']),
        ];
    }
    
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('feedback_rating', [$this, 'feedbackRating'], [
                'is_safe' => ['html']
            ]),
        ];
    }
    
    public function feedbackRating($rating, $scale = 5)
    {
        $ratingWidth = ($rating / $scale) * 100;
        $templating = $this->container->get('templating');
        
        return $templating->render('LessonBundle:Twig:_feedback_rating.html.twig', [
            'width' => $ratingWidth,
            'scale' => $scale,
            'rating' => $rating,
        ]);
    }
    
    public function feedbackStatus(Feedback $feedback)
    {
        switch($feedback->getStatus()) {
            case Feedback::STATUS_PENDING:
                return 'status.pending';
            case Feedback::STATUS_ACCEPTED:
                return 'status.accepted';
            case Feedback::STATUS_REJECTED:
                return 'status.rejected';
        }
        
        throw new LogicException('This could should never be reached.');
    }
    
    public function feedbackStatusLabel(Feedback $feedback)
    {
        switch($feedback->getStatus()) {
            case Feedback::STATUS_PENDING:
                return 'label label-warning';
            case Feedback::STATUS_ACCEPTED:
                return 'label label-success';
            case Feedback::STATUS_REJECTED:
                return 'label label-danger';
        }
        
        throw new LogicException('This could should never be reached.');
    }
}
