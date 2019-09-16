<?php

namespace LessonBundle\Model;

use LessonBundle\Entity\Lesson;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Translation\TranslatorInterface;

class FileLessonManager extends AbstractLessonManager
{
    /**
     * @var string
     */
    protected $lessonDir;

    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @param string $dir
     */
    public function setLessonDirectory($dir)
    {
        $this->lessonDir = $dir;
    }

    /**
     * @param string $dir
     */
    public function setTranslator($trans)
    {
        $this->translator = $trans;
    }

    /**
     * @param UsernamePasswordToken $token
     * @param Lesson $lesson
     *
     * @return mixed|void
     */
    public function initializeLesson($token, Lesson $lesson)
    {
        $lesson->setStatus(Lesson::STATUS_INITIALIZED);
        $subDir = intdiv($lesson->getId(), 10);
        $path = $this->lessonDir
                .'/'.$subDir
                .'/'.$lesson->getId();
        $fileSystem = new Filesystem();
        $fileSystem->dumpFile(
            $path.'/lesson',
            $this->translator->trans(
                'classroom.initial_text', [], 'backend',
                $lesson->getPurchase()->getStudent()->getUiLanguage()
            )
        );
        $lesson->setWhiteboardDir($path);
        $this->update($lesson);
        $otSession = $this->openTokHelper->createSession();
        $lesson->setOpentokSessionId($otSession->getSessionId());
    }
}