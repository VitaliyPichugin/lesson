<?php

namespace LessonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Feedback
 *
 * @ORM\Table(name="feedback")
 * @ORM\Entity(repositoryClass="LessonBundle\Repository\FeedbackRepository")
 */
class Feedback
{
    const STATUS_PENDING = 0;
    const STATUS_ACCEPTED = 1;
    const STATUS_REJECTED = 2;
    
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
    private $status = self::STATUS_PENDING;

    /**
     * @var int
     *
     * @Assert\NotNull()
     * @Assert\Range(min=1, max=5)
     * @ORM\Column(name="langu_rating", type="smallint")
     */
    private $languRating = 0;

    /**
     * @var int
     *
     * @Assert\NotNull()
     * @Assert\Range(min=1, max=5)
     * @ORM\Column(name="lesson_rating", type="smallint")
     */
    private $lessonRating = 0;

    /**
     * @var int
     *
     * @Assert\NotNull()
     * @Assert\Range(min=1, max=5)
     * @ORM\Column(name="teacher_rating", type="smallint")
     */
    private $teacherRating = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="teacher_comment", type="string", length=500, nullable=true)
     */
    private $teacherComment;

    /**
     * @var string
     *
     * @ORM\Column(name="lesson_comment", type="string", length=500, nullable=true)
     */
    private $lessonComment;

    /**
     * @var Lesson
     *
     * @ORM\OneToOne(targetEntity="Lesson", inversedBy="feedback")
     * @ORM\JoinColumn(name="lesson_id", referencedColumnName="id")
     */
    protected $lesson;

    /**
     * @var Language
     *
     * @ORM\ManyToOne(targetEntity="IntlBundle\Entity\Language")
     * @ORM\JoinColumn(name="language_id", referencedColumnName="id", nullable=true)
     */
    protected $language;

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

    public function getAverageRating()
    {
        return ($this->teacherRating + $this->lessonRating) / 2;
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
     * @return Feedback
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
     * Set languRating
     *
     * @param integer $languRating
     *
     * @return Feedback
     */
    public function setLanguRating($languRating)
    {
        $this->languRating = $languRating;

        return $this;
    }

    /**
     * Get languRating
     *
     * @return int
     */
    public function getLanguRating()
    {
        return $this->languRating;
    }

    /**
     * Set lessonRating
     *
     * @param integer $lessonRating
     *
     * @return Feedback
     */
    public function setLessonRating($lessonRating)
    {
        $this->lessonRating = $lessonRating;

        return $this;
    }

    /**
     * Get lessonRating
     *
     * @return int
     */
    public function getLessonRating()
    {
        return $this->lessonRating;
    }

    /**
     * Set teacherRating
     *
     * @param integer $teacherRating
     *
     * @return Feedback
     */
    public function setTeacherRating($teacherRating)
    {
        $this->teacherRating = $teacherRating;

        return $this;
    }

    /**
     * Get teacherRating
     *
     * @return int
     */
    public function getTeacherRating()
    {
        return $this->teacherRating;
    }

    /**
     * Set teacherComment
     *
     * @param string $teacherComment
     *
     * @return Feedback
     */
    public function setTeacherComment($teacherComment)
    {
        $this->teacherComment = $teacherComment;

        return $this;
    }

    /**
     * Get teacherComment
     *
     * @return string
     */
    public function getTeacherComment()
    {
        return $this->teacherComment;
    }

    /**
     * Set lessonComment
     *
     * @param string $lessonComment
     *
     * @return Feedback
     */
    public function setLessonComment($lessonComment)
    {
        $this->lessonComment = $lessonComment;

        return $this;
    }

    /**
     * Get lessonComment
     *
     * @return string
     */
    public function getLessonComment()
    {
        return $this->lessonComment;
    }

    /**
     * Set lesson
     *
     * @param \LessonBundle\Entity\Lesson $lesson
     *
     * @return Feedback
     */
    public function setLesson(\LessonBundle\Entity\Lesson $lesson = null)
    {
        $this->lesson = $lesson;

        return $this;
    }

    /**
     * Get lesson
     *
     * @return \LessonBundle\Entity\Lesson
     */
    public function getLesson()
    {
        return $this->lesson;
    }

    /**
     * Set language
     *
     * @param \IntlBundle\Entity\Language $language
     *
     * @return Feedback
     */
    public function setLanguage(\IntlBundle\Entity\Language $language = null)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return \IntlBundle\Entity\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Feedback
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
     * @return Feedback
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
}
