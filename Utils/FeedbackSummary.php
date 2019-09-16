<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Utils;

/**
 * Description of FeedbackSummary
 *
 */
class FeedbackSummary
{
    /**
     * @var integer
     */
    private $reviewsQuantity;
    
    /**
     *
     * @var string|BcMathNumber
     */
    private $reviewsAverage;
    
    /**
     *
     * @var array
     */
    private $reviewsDistribution;
    
    public function __construct($summaryData) 
    {
        $this->reviewsAverage = current($summaryData['average']);
        $this->reviewsDistribution = $this->processReviewsDistribution($summaryData['distribution']);
        $this->reviewsQuantity = $this->calculateReviewsQuantity($this->reviewsDistribution);
    }
    
    protected function processReviewsDistribution($distribution)
    {
        $distributionArray = array_reverse(array_fill(1, 5, 0), true);
        
        foreach($distribution as $item) {
            $distributionArray[$item['teacherRating']] = (int)$item['distribution'];
        }
        
        return $distributionArray;
    }
    
    protected function calculateReviewsQuantity($array)
    {
        return array_sum($array);
    }
    
    public function getReviewsDistribution()
    {
        return $this->reviewsDistribution;
    }
    
    public function getReviewsQuantity()
    {
        return $this->reviewsQuantity;
    }
    
    public function getReviewsAverage()
    {
        return $this->reviewsAverage;
    }
}
