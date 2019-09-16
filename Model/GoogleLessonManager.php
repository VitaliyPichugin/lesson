<?php

namespace LessonBundle\Model;

use Google_Service_Drive;
use GoogleApiBundle\Factory\GoogleClientFactory;
use GoogleApiBundle\Factory\GoogleServiceFactory;
use LessonBundle\Entity\Lesson;
use UserBundle\Security\Authentication\Token\GoogleAuthToken;

/**
 * Description of LessonManager
 *
 */
class GoogleLessonManager extends AbstractLessonManager
{

    protected $class = Lesson::class;

    /**
     * @var GoogleClientFactory
     */
    protected $googleClientFactory;

    /**
     * @var GoogleServiceFactory
     */
    protected $googleServiceFactory;

    public function setGoogleServices(GoogleClientFactory $googleClientFactory, GoogleServiceFactory $googleServiceFactory)
    {
        $this->googleClientFactory = $googleClientFactory;
        $this->googleServiceFactory = $googleServiceFactory;
    }

    /**
     * @param GoogleAuthToken $token
     * @param Lesson $lesson
     */
    public function initializeLesson($token, Lesson $lesson)
    {
        $lesson->setStatus(Lesson::STATUS_INITIALIZED);
        $googleClient = $this->googleClientFactory->getDefaultClient();
        $googleClient->setAccessToken($token->getRawToken());

        /* @var $driveService Google_Service_Drive */
        $driveService = $this->googleServiceFactory->getServiceInstance('drive', $googleClient);

        $file = $this->googleServiceFactory->getDriveFileInstance();
        $file->setName('Lesson ' . $lesson->getId() . ' whiteboard file');
        $file->setDescription('This file contains the virtual whiteboard from your Langu lesson, but it cannot be opened here. To review, please log into Langu and click My Lessons > Past Lessons.');

        $createdFile = $driveService->files->create($file, ['fields' => 'id']);

        $permission = $this->googleServiceFactory->getDriveFilePermissionInstance();
        $permission->setEmailAddress($lesson->getPurchase()->getStudent()->getGoogleEmail());

        $driveService->permissions->create($createdFile->id, $permission, [
            'fields' => 'id',
            'sendNotificationEmail' => false,
        ]);
        $lesson->setDriveFileId($createdFile->id);

        $otSession = $this->openTokHelper->createSession();
        $lesson->setOpentokSessionId($otSession->getSessionId());

        $this->update($lesson);
    }
}
