<?php

namespace LessonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Log
 *
 * @ORM\Table(name="lesson_log")
 * @ORM\Entity(repositoryClass="LessonBundle\Repository\LogRepository")
 */
class Log
{   
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Lesson
     *
     * @ORM\ManyToOne(targetEntity="Lesson")
     * @ORM\JoinColumn(name="lesson_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $lesson;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * 
     * @ORM\Column(name="date", type="datetime")
     */
    protected $date;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint")
     */
    protected $type;
   
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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Log
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return Log
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set lesson
     *
     * @param \LessonBundle\Entity\Lesson $lesson
     *
     * @return Log
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
}
