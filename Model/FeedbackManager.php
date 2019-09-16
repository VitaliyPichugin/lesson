<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Model;

use AppBundle\Model\BaseManager;
use Doctrine\ORM\QueryBuilder;
use LessonBundle\Entity\Feedback;

/**
 * Description of FeedbackManager
 *
 */
class FeedbackManager extends BaseManager
{
    protected $class = Feedback::class;
    
    public function getTeacherReviewsQuery($teacher, $count = false)
    {
        if($count) {
            return $this->getCountQueryForTeacherReviews($teacher);
        }
        
        return $this->getQueryForTeacherReviews($teacher);
    }
    
    public function getLatestTeacherReviews($teacher, $limit = 5)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->getQueryForTeacherReviews($teacher);
        $qb->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();
    }
    
    public function getTeacherReviewsSummary($teacher)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->getBaseQueryForTeacherReviews($teacher);
        $qb
                ->select($qb->expr()->countDistinct('f.id').' AS distribution')
                ->addSelect('f.teacherRating')
                ->groupBy(('f.teacherRating'))
        ;
        
        $distribution = $qb->getQuery()->getArrayResult();
        $average = $this->getTeacherReviewsAverage($teacher);
        
        return [
            'distribution' => $distribution,
            'average' => $average,
        ];
    }
    
    public function getTeacherReviewsAverage($teacher)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->getBaseQueryForTeacherReviews($teacher);
        $qb
                ->select($qb->expr()->avg('f.teacherRating'))
                ->addSelect($qb->expr()->count(('f.id')))
        ;
                
        $result = $qb->getQuery()->getOneOrNullResult();
        
        if(!$result) {
            return [
                'rating' => 0,
                'count' => 0,
            ];
        }
        
        return [
            'rating' => $result[1],
            'count' => (int)$result[2],
        ];
    }

    public function getTeachersReviewsAverage($teacherIds)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('f');
        $qb
                ->select($qb->expr()->avg('f.teacherRating'))
                ->addSelect($qb->expr()->count(('f.id')))
                ->addSelect('t.id')
                ->leftJoin('f.lesson', 'l')
                ->leftJoin('l.purchase', 'p')
                ->leftJoin('p.teacher', 't')
                ->andWhere($qb->expr()->in('t.id', ':teacherIds'))
                ->andWhere($qb->expr()->eq('f.status', ':fStatus'))
                ->groupBy('t.id')
                ->setParameter(':teacherIds', $teacherIds)
                ->setParameter(':fStatus', Feedback::STATUS_ACCEPTED)
        ;

        $result = $qb->getQuery()->getResult();
        $resultProcessed = [];
        
        foreach($result as $res) {
            $resultProcessed[$res['id']] = [
                'rating' =>  $res[1],
                'count' => $res[2],
            ];
        }
        
        return $resultProcessed;
    }
    
    protected function getBaseQueryForTeacherReviews($teacher)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('f');
        $qb
                ->leftJoin('f.lesson', 'l')
                ->leftJoin('l.purchase', 'p')
                ->andWhere('p.teacher = :teacher')
                ->andWhere($qb->expr()->eq('f.status', ':fStatus'))
                ->setParameter(':teacher', $teacher)
                ->setParameter(':fStatus', Feedback::STATUS_ACCEPTED)
        ;
        
        return $qb;
    }
    
    protected function getQueryForTeacherReviews($teacher)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->getBaseQueryForTeacherReviews($teacher);
        $qb
                ->leftJoin('p.student', 's')
                ->addSelect('s')
                ->addSelect('partial p.{id}')
                ->addSelect('partial l.{id}')
                ->andWhere($qb->expr()->isNotNull('f.teacherComment'))
                ->andWhere($qb->expr()->eq('f.status', ':fStatus'))
                ->addOrderBy('f.createdAt', 'DESC')
        ;
        
        return $qb;
    }
    
    protected function getCountQueryForTeacherReviews($teacher)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->getBaseQueryForTeacherReviews($teacher);
        $qb
                ->andWhere($qb->expr()->isNotNull('f.teacherComment'))
                ->select($qb->expr()->countDistinct('f.id'))
        ;
        
        return $qb;
    }
}
