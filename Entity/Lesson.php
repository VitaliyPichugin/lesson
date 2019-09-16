<?php

namespace LessonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use TeachingBundle\Entity\Purchase;

/**
 * Lesson
 *
 * @ORM\Table(name="lesson")
 * @ORM\Entity(repositoryClass="LessonBundle\Repository\LessonRepository")
 */
class Lesson
{

    const STATUS_CREATED = 0;
    const STATUS_INITIALIZED = 1;
    const STATUS_FINISHED = 2;
    const STATUS_CANCELLED = 3;
    const STATUS_REFUNDED = 4;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var Transaction
     *
     * @ORM\ManyToOne(targetEntity="MangopayBundle\Entity\Transaction")
     * @ORM\JoinColumn(name="payment_transaction_id", referencedColumnName="id", nullable=true)
     */
    private $paymentTransaction;

    /**
     * @var string
     *
     * @ORM\Column(name="drive_file_id", type="string", length=100, nullable=true)
     */
    private $driveFileId;

    /**
     * @var null|string
     *
     * @ORM\Column(name="whiteboard_dir", type="string", nullable=true)
     */
    private $whiteboardDir;

    /**
     * @var string
     *
     * @ORM\Column(name="opentok_session_id", type="string", length=100, nullable=true)
     */
    private $opentokSessionId;

    /**
     * @var Purchase
     *
     * @ORM\ManyToOne(targetEntity="TeachingBundle\Entity\Purchase")
     * @ORM\JoinColumn(name="purchase_id", referencedColumnName="id")
     */
    private $purchase;

    /**
     * @var int
     *
     * @ORM\Column(name="ordinal", type="smallint")
     */
    private $ordinal;

    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="AvailabilityBundle\Entity\Slot", cascade={"persist"})
     * @ORM\JoinTable(name="lesson__slot", 
     *      joinColumns={@ORM\JoinColumn(name="lesson_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="slot_id", referencedColumnName="id")}
     *      )
     * @ORM\OrderBy({"date"="ASC"})
     */
    private $slots;

    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="MediaBundle\Entity\Resource", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="lesson__resource", 
     *      joinColumns={@ORM\JoinColumn(name="lesson_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="resource_id", referencedColumnName="id")}
     *      )
     * @ORM\OrderBy({"name"="ASC"})
     */
    private $resources;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime")
     */
    private $startDate;

    /**
     * @var Feedback
     *
     * @ORM\OneToOne(targetEntity="Feedback", fetch="EXTRA_LAZY", mappedBy="lesson")
     */
    protected $feedback;

    /**
     * @var string
     *
     * @ORM\Column(name="objectives", type="array")
     */
    protected $objectives = [];

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="reminder_at", type="datetime", nullable=true)
     */
    private $reminderAt;

    public function isTrial()
    {
        return (null === $this->getPurchase()->getPaymentTransaction()) ? true : false;
    }
    
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Lesson
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set driveFileId
     *
     * @param string $driveFileId
     *
     * @return Lesson
     */
    public function setDriveFileId($driveFileId)
    {
        $this->driveFileId = $driveFileId;

        return $this;
    }

    /**
     * Get driveFileId
     *
     * @return string
     */
    public function getDriveFileId()
    {
        return $this->driveFileId;
    }

    /**
     * Returns whiteboard dir if exists.
     *
     * @return null|string
     */
    public function getWhiteboardDir()
    {
        return $this->whiteboardDir;
    }

    /**
     * Sets whiteboard dir.
     *
     * @param string $whiteboardDir
     *                             
     * @return Lesson
     */
    public function setWhiteboardDir($whiteboardDir)
    {
        $this->whiteboardDir = $whiteboardDir;
        return $this;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Lesson
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Lesson
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Lesson
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set purchase
     *
     * @param \TeachingBundle\Entity\Purchase $purchase
     *
     * @return Lesson
     */
    public function setPurchase(\TeachingBundle\Entity\Purchase $purchase = null)
    {
        $this->purchase = $purchase;

        return $this;
    }

    /**
     * Get purchase
     *
     * @return \TeachingBundle\Entity\Purchase
     */
    public function getPurchase()
    {
        return $this->purchase;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->slots = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add slot
     *
     * @param \AvailabilityBundle\Entity\Slot $slot
     *
     * @return Lesson
     */
    public function addSlot(\AvailabilityBundle\Entity\Slot $slot)
    {
        $slot->setStatus(\AvailabilityBundle\Entity\Slot::STATUS_OCCUPIED);
        $this->slots[] = $slot;

        return $this;
    }

    /**
     * Remove slot
     *
     * @param \AvailabilityBundle\Entity\Slot $slot
     */
    public function removeSlot(\AvailabilityBundle\Entity\Slot $slot)
    {
        $this->slots->removeElement($slot);
    }

    /**
     * Get slots
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSlots()
    {
        return $this->slots;
    }

    /**
     * Set feedback
     *
     * @param \LessonBundle\Entity\Feedback $feedback
     *
     * @return Lesson
     */
    public function setFeedback(\LessonBundle\Entity\Feedback $feedback = null)
    {
        $this->feedback = $feedback;

        return $this;
    }

    /**
     * Get feedback
     *
     * @return \LessonBundle\Entity\Feedback
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * Set opentokSessionId
     *
     * @param string $opentokSessionId
     *
     * @return Lesson
     */
    public function setOpentokSessionId($opentokSessionId)
    {
        $this->opentokSessionId = $opentokSessionId;

        return $this;
    }

    /**
     * Get opentokSessionId
     *
     * @return string
     */
    public function getOpentokSessionId()
    {
        return $this->opentokSessionId;
    }

    /**
     * Add resource
     *
     * @param \MediaBundle\Entity\Resource $resource
     *
     * @return Lesson
     */
    public function addResource(\MediaBundle\Entity\Resource $resource)
    {
        $this->resources[] = $resource;

        return $this;
    }

    /**
     * Remove resource
     *
     * @param \MediaBundle\Entity\Resource $resource
     */
    public function removeResource(\MediaBundle\Entity\Resource $resource)
    {
        $this->resources->removeElement($resource);
    }

    /**
     * Get resources
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Set objectives
     *
     * @param array $objectives
     *
     * @return Lesson
     */
    public function setObjectives($objectives)
    {
        $this->objectives = $objectives;

        return $this;
    }

    /**
     * Get objectives
     *
     * @return array
     */
    public function getObjectives()
    {
        return $this->objectives;
    }

    /**
     * Set paymentTransaction
     *
     * @param \MangopayBundle\Entity\Transaction $paymentTransaction
     *
     * @return Lesson
     */
    public function setPaymentTransaction(\MangopayBundle\Entity\Transaction $paymentTransaction = null)
    {
        $this->paymentTransaction = $paymentTransaction;

        return $this;
    }

    /**
     * Get paymentTransaction
     *
     * @return \MangopayBundle\Entity\Transaction
     */
    public function getPaymentTransaction()
    {
        return $this->paymentTransaction;
    }

    /**
     * Set ordinal
     *
     * @param integer $ordinal
     *
     * @return Lesson
     */
    public function setOrdinal($ordinal)
    {
        $this->ordinal = $ordinal;

        return $this;
    }

    /**
     * Get ordinal
     *
     * @return integer
     */
    public function getOrdinal()
    {
        return $this->ordinal;
    }


    /**
     * Set reminderAt
     *
     * @param \DateTime $reminderAt
     *
     * @return Lesson
     */
    public function setReminderAt($reminderAt)
    {
        $this->reminderAt = $reminderAt;

        return $this;
    }

    /**
     * Get reminderAt
     *
     * @return \DateTime
     */
    public function getReminderAt()
    {
        return $this->reminderAt;
    }
}
