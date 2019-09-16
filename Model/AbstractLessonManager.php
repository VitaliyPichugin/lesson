<?php

namespace LessonBundle\Model;

use AppBundle\Model\BaseManager;
use AvailabilityBundle\Entity\Slot;
use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;
use LessonBundle\Entity\Lesson;
use LessonBundle\Utils\LessonSummary;
use LessonBundle\Utils\OpenTokHelper;
use OpenTok\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use UserBundle\Entity\AbstractUser;
use UserBundle\Entity\StudentUser;
use UserBundle\Entity\TeacherUser;

/**
 * Description of LessonManager
 *
 */
abstract class AbstractLessonManager extends BaseManager
{

    protected $class = Lesson::class;

    /**
     * @var OpenTokHelper
     */
    protected $openTokHelper;

    public function setOpenTokHelper(OpenTokHelper $openTokHelper)
    {
        $this->openTokHelper = $openTokHelper;
    }
    public function getUpcomingLessons(DateTime $date, $limit = 25)
    {
        $now = Carbon::instance($date);
        $ahead = $now->copy()->addHours(1);

        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('l');
        $qb
            ->join('l.purchase', 'p')
            ->join('p.teacher', 't')
            ->join('p.student', 's')
            ->join('p.language', 'la')
            ->addSelect('p, t, s, la')
            ->where('l.reminderAt IS NULL')
            ->andWhere($qb->expr()->between('l.startDate', ':now', ':hourAhead'))
            ->andWhere($qb->expr()->in('l.status', ':statuses'))
            ->orderBy('l.startDate', 'ASC')
            ->addOrderBy('l.updatedAt', 'DESC')
            ->setParameter(':now', $now)
            ->setParameter(':hourAhead', $ahead)
            ->setParameter(':statuses', [Lesson::STATUS_CREATED, Lesson::STATUS_INITIALIZED])
            ->setMaxResults($limit)
        ;

        return $qb->getQuery()->getResult();
    }

    public function getUserLessonsQuery(AbstractUser $user, $history = false, $count = false)
    {
        if (!$count) {
            return $this->userLessonsQuery($user, $history);
        }

        return $this->userLessonsCountQuery($user, $history);
    }

    protected function getUserLessonsFilter(QueryBuilder $qb, $history)
    {
        if (!$history) {
            $qb
                ->andWhere($qb->expr()->in('l.status', ':statuses'))
                ->andWhere('l.startDate > :date')
                ->orderBy('l.startDate', 'ASC')
                ->setParameter(':date', Carbon::now('UTC')->subMinutes(30))
                ->setParameter(':statuses', [
                    Lesson::STATUS_INITIALIZED,
                    Lesson::STATUS_CREATED,
                ])
            ;
        } else {
            $qb
                ->andWhere($qb->expr()->orX($qb->expr()->andX('l.startDate <= :date', $qb->expr()->in('l.status', ':statuses')), 'l.status = :status'))
                ->orderBy('l.startDate', 'DESC')
                ->setParameter(':date', Carbon::now('UTC')->subMinutes(30))
                ->setParameter(':status', Lesson::STATUS_FINISHED)
                ->setParameter(':statuses', [
                    Lesson::STATUS_INITIALIZED,
                    Lesson::STATUS_CREATED,
                ])
            ;
        }
    }

    protected function userLessonsCountQuery(AbstractUser $user, $history)
    {
        $userType = in_array('ROLE_STUDENT', $user->getRoles()) ? 'student' : 'teacher';

        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('l');
        $qb
            ->select($qb->expr()->countDistinct('l.id'))
            ->leftJoin('l.purchase', 'p')
            //                ->leftJoin('l.slots', 's')
            ->where(sprintf('p.%s = :user', $userType))
            ->setParameter(':user', $user);

        $this->getUserLessonsFilter($qb, $history);

        return $qb->getQuery();
    }

    protected function userLessonsQuery(AbstractUser $user, $history)
    {
        $userType = in_array('ROLE_STUDENT', $user->getRoles()) ? 'student' : 'teacher';

        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('l');
        $qb
            ->leftJoin('l.purchase', 'p')
            ->leftJoin('p.language', 'pl')
            ->leftJoin('l.feedback', 'f')
            ->addSelect('p')
            ->addSelect('f')
            ->addSelect('pl')
            ->where(sprintf('p.%s = :user', $userType))
            ->setParameter(':user', $user);

        if($userType === 'student') {
            $qb
                ->leftJoin('p.teacher', 'pt')
                ->addSelect('pt');
        } else {
            $qb
                ->leftJoin('p.student', 'ps')
                ->addSelect('ps');
        }

        $this->getUserLessonsFilter($qb, $history);

        $query = $qb->getQuery();
        $query->useQueryCache(false);
        $query
            ->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class
            )
            ->setHint(
                TranslatableListener::HINT_FALLBACK, 1
            );

        return $query;
    }

    public function cancelLesson(Lesson $lesson)
    {
        if (!$lesson->isTrial()) {
            $lesson->setStatus(Lesson::STATUS_CANCELLED);
        } else {
            $lesson->setStatus(Lesson::STATUS_REFUNDED);
        }

        /* @var $slot Slot */
        foreach ($lesson->getSlots() as $slot) {
            $slot->setStatus(Slot::STATUS_FREE);
            $lesson->removeSlot($slot);
            $this->getManager()->persist($slot);
        }

        $this->update($lesson);
    }

    /**
     * @param StudentUser $user
     *
     * @return mixed
     *
     * @throws NonUniqueResultException
     */
    public function hasFeedbackMissingLessons(StudentUser $user)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('l');
        $qb
            ->select('l.id')
            ->leftJoin('l.feedback', 'f')
            ->leftJoin('l.purchase', 'p')
            ->where($qb->expr()->isNull('f.id'))
            ->andWhere('l.status = :status')
            ->andWhere('p.student = :user')
            ->setParameter(':status', Lesson::STATUS_FINISHED)
            ->setParameter(':user', $user)
            ->setMaxResults(1)
        ;
        return $qb->getQuery()
            ->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @param StudentUser $user
     * @param TeacherUser $teacher
     *
     * @return bool
     *
     * @throws NonUniqueResultException
     */
    public function isKnownTeacher(StudentUser $user, TeacherUser $teacher)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('l');
        $qb
            ->select('l.id')
            ->leftJoin('l.purchase', 'p')
            ->andWhere($qb->expr()->neq('l.status', ':status'))
            ->andWhere('p.student = :user')
            ->andWhere('p.teacher = :teacher')
            ->setParameter(':status', Lesson::STATUS_REFUNDED)
            ->setParameter(':user', $user)
            ->setParameter(':teacher', $teacher)
            ->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();
        return is_null($result) ? false : true;
    }

    public function canBookTrial(StudentUser $user, $maxTrials = 2)
    {
        $dt = Carbon::now('UTC');

        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('l');
        $qb
            ->select('l.id')
            ->leftJoin('l.purchase', 'p')
            ->andWhere($qb->expr()->neq('l.status', ':status'))
            ->andWhere('p.student = :user')
            ->andWhere('p.price = 0')
            ->andWhere('p.createdAt > :limitDate')
            ->setParameter(':user', $user)
            ->setParameter(':status', Lesson::STATUS_REFUNDED)
            ->setParameter(':limitDate', $dt->copy()->subDays(30))
            ->setMaxResults($maxTrials)
        ;

        $result = $qb->getQuery()->getScalarResult();

        if(count($result) >= $maxTrials) {
            return false;
        } else {
            return true;
        }
    }

    public function getKnownTeachers(StudentUser $user, array $teachers)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('l');
        $qb
            ->select('l.id AS HIDDEN lid')
            ->addSelect('t.id as tid')
            ->leftJoin('l.purchase', 'p')
            ->leftJoin('p.teacher', 't')
            ->andWhere($qb->expr()->neq('l.status', ':status'))
            ->andWhere('p.student = :user')
            ->andWhere($qb->expr()->in('p.teacher', ':teachers'))
            ->andWhere('l.id IS NOT NULL')
            ->setParameter(':status', Lesson::STATUS_REFUNDED)
            ->setParameter(':user', $user)
            ->setParameter(':teachers', $teachers)
            ->addGroupBy('p.teacher')
        ;

        $res = $qb->getQuery()->getScalarResult();
        $calculated = array_flip(array_column($res, 'tid'));

        return $calculated;
    }

    /**
     * @param TokenInterface $token
     * @param Lesson $lesson
     *
     * @return mixed
     */
    abstract public function initializeLesson($token, Lesson $lesson);

    public function generateOpenTokToken(Lesson $lesson, AbstractUser $user)
    {
        $tokenOptions = [
            'role' => Role::PUBLISHER,
            'data' => sprintf('id:%s;name:%s', $user->getId(), $user->getFullName()),
        ];

        return $this->openTokHelper->generateToken($lesson->getOpentokSessionId(), $tokenOptions);
    }

    public function finishLesson(Lesson $lesson)
    {
        $lesson->setStatus(Lesson::STATUS_FINISHED);

        $this->update($lesson);
    }

    /**
     * @param $lessonId
     * @param AbstractUser $user
     *
     * @return mixed
     *
     * @throws NonUniqueResultException
     */
    public function getLessonForClassroom($lessonId, AbstractUser $user)
    {
        $userType = in_array('ROLE_STUDENT', $user->getRoles()) ? 'student' : 'teacher';

        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('l');
        $qb
            ->leftJoin('l.purchase', 'p')
            ->leftJoin('l.slots', 's')
            ->leftJoin('p.language', 'pl')
            ->leftJoin('p.student', 'ps')
            ->leftJoin('l.feedback', 'lf')
            ->leftJoin('l.resources', 'lr')
            ->addSelect('p')
            ->addSelect('s')
            ->addSelect('partial pl.{id,name}')
            ->addSelect('partial lf.{id}')
            ->addSelect('lr')
            ->addSelect('ps')
            ->where(sprintf('p.%s = :user', $userType))
            ->andWhere('l.id = :id')
            ->setParameter(':user', $user)
            ->setParameter(':id', $lessonId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getLessonsPendingForPayment($limit = 10)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('l');
        $qb
            ->select('l')
            ->leftJoin('l.feedback', 'f')
            //                ->leftJoin(Feedback::class, 'f', Query\Expr\Join::WITH, 'l.id = f.lesson_id')
            ->join('l.purchase', 'p')
            ->join('p.student', 's')
            ->join('p.teacher', 't')
            ->join('p.paymentTransaction', 'pt')
            ->join('pt.creditedWallet', 'ptcw')
            ->addSelect('p')
            ->addSelect('s')
            ->addSelect('t')
            ->addSelect('pt')
            ->addSelect('ptcw')
            ->where($qb->expr()->isNull('l.paymentTransaction'))
            ->andWhere($qb->expr()->isNotNull('p.paymentTransaction')) // filter trials
            ->orderBy('f.createdAt', 'ASC')
            ->setParameter(':fRating', '2')
            ->setMaxResults($limit)
        ;

        // Lesson without feedback conditions
        $feedbackLessAnd = $qb->expr()->andX();
        $feedbackLessAnd->add($qb->expr()->eq('l.status', ':lStatus'));
        $feedbackLessAnd->add($qb->expr()->isNull('f.id'));
        $feedbackLessAnd->add($qb->expr()->lt('l.startDate', ':lDate'));

        // Lesson with feedback and rating above 2
        $ratingCond = $qb->expr()->andX();
        $ratingCond->add($qb->expr()->gt('f.teacherRating', ':fRating'));
        $ratingCond->add($qb->expr()->isNotNull('f.id'));

        $dt = Carbon::now('UTC')->subDay()->addMinutes(30);

        $qb->andWhere($qb->expr()->orX($feedbackLessAnd, $ratingCond));
        $qb->setParameter(':lStatus', Lesson::STATUS_FINISHED);
        $qb->setParameter(':lDate', $dt);

        return $qb->getQuery()->getResult();
    }

    public function getPreviousLessonsSummaryByPurchaseId($purchaseId)
    {
        /* @var $qb QueryBuilder */
        $qb = $this->repository->createQueryBuilder('l');
        $qb
            ->select('SUM(pt.amount) AS amount')
            ->addSelect('COUNT(pt.id) AS lessons')
            ->join('l.paymentTransaction', 'pt')
            ->join('l.purchase', 'p')
            ->where('p.id = :purchaseId')
        ;

        try {
            $result = $qb->getQuery()->getScalarResult();
        } catch (NoResultException $e) {
            return new LessonSummary();
        }

        $summary = new LessonSummary();
        $summary->amount = $result[0]['amount'];
        $summary->lessons = $result[0]['lessons'];

        return $summary;
    }

}
