<?php

namespace LessonBundle\Model;

use AppBundle\Model\BaseManager;
use Doctrine\Common\Collections\Criteria;
use LessonBundle\Entity\Lesson;
use TeachingBundle\Model\PurchaseManager;
use UserBundle\Entity\AbstractUser;

class GoogleMigrateManager extends BaseManager
{
    /**
     * @var string
     */
    protected $class = Lesson::class;

    /**
     * @var PurchaseManager
     */
    private $purchaseManager;

    /**
     * @var string
     */
    private $lessonDir;

    /**
     * @param PurchaseManager $manager
     */
    public function setPurchaseManager(PurchaseManager $manager)
    {
        $this->purchaseManager = $manager;
    }

    /**
     * @param string $dir
     */
    public function setLessonDirectory($dir)
    {
        $this->lessonDir = $dir;
    }

    public function getGoogleLessonsByUser(AbstractUser $user)
    {
        $purchaseIDs = $this->purchaseManager->getPurchaseIDsByUser($user);
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->in('purchase', $purchaseIDs))
            ->andWhere(Criteria::expr()->isNull('whiteboardDir'))
            ->andWhere(Criteria::expr()->neq('driveFileId', null));
        return $this->repository->matching($criteria)
            ->map(
                function (Lesson $lesson) {
                    return [
                        'id'     => $lesson->getId(),
                        'fileId' => $lesson->getDriveFileId(),
                    ];
                }
            )
            ->getValues();
    }
}