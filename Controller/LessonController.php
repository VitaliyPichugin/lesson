<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Controller;

use AppBundle\Controller\JsonController;
use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use function \json_encode;
use LessonBundle\Entity\Lesson;
use LessonBundle\Event\LessonEvent;
use LessonBundle\Event\LessonEvents;
use LessonBundle\Form\LessonResourcesFormType;
use LessonBundle\Model\FileLessonManager;
use LessonBundle\Model\GoogleLessonManager;
use LessonBundle\Model\GoogleMigrateManager;
use LessonBundle\Security\LessonVoterActions;
use Pusher\Pusher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use TeachingBundle\Entity\Purchase;
use TeachingBundle\Model\PurchaseManager;
use UserBundle\Security\Authentication\Token\GoogleAuthToken;

/**
 * Description of LessonController
 *
 */
class LessonController extends JsonController
{
    /**
     * @var FileLessonManager|GoogleLessonManager
     */
    private $lessonManager;

    /**
     * @var PurchaseManager
     */
    private $purchaseManager;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->lessonManager = $this->container->get('lesson.lesson_manager');
        $this->purchaseManager = $this->container->get(
            'teaching.purchase_manager'
        );
        $this->eventDispatcher = $this->container->get('event_dispatcher');
    }

    public function cancelAction(Request $request)
    {
        $user = $this->getUser();
        $lessonId = $request->request->get('lessonId');
        $lesson = $this->lessonManager->find($lessonId);

        /* @var $lesson Lesson */
        if (!$lesson
            || (!$lesson->getPurchase()->getStudent()->isEqualTo($user)
                && !$lesson->getPurchase()->getTeacher()->isEqualTo(
                    $user
                ))) {
            return $this->createJsonResponse(
                'lesson.cancel.no_permissions', [], 400
            );
        }

        if ($this->isGranted(LessonVoterActions::CANCEL)) {
            return $this->createJsonResponse(
                'lesson.cancel.no_permissions', [], 400
            );
        }

        $this->lessonManager->cancelLesson($lesson);

        $purchase = $lesson->getPurchase();

        if (!$lesson->isTrial()) {
            $purchase->setLessonsLeft($purchase->getLessonsLeft() + 1);
            $purchase->setStatus(Purchase::STATUS_PAID);
            $this->purchaseManager->update($purchase);
        } else {
            $purchase->setStatus(Purchase::STATUS_REFUNDED);
            $this->purchaseManager->update($purchase);
        }

        $ev = new LessonEvent($lesson, $user);
        $this->eventDispatcher->dispatch(LessonEvents::CANCELLED, $ev);

        return $this->createJsonResponse('lesson.removed', [], 200);
    }

    public function enterAction(Request $request, $lessonId)
    {
        /** @var AbstractToken $token */
        $token = $this->container->get('security.token_storage')->getToken();
        $user = $this->getUser();
        /** @var null|Lesson $lesson */
        $lesson = $this->lessonManager->getLessonForClassroom($lessonId, $user);
        if (!$lesson || !$this->isGranted(LessonVoterActions::ENTER, $lesson)) {
            throw $this->createNotFoundException('lesson.not_found');
        }
        if ($this->isGranted(LessonVoterActions::INITIALIZE, $lesson)) {
            $this->lessonManager->initializeLesson($token, $lesson);
            $this->eventDispatcher->dispatch(
                LessonEvents::CLASSROOM_INITIALIZED,
                new LessonEvent($lesson, $user)
            );
        } elseif ($request->getUri() !== $request->headers->get('referer')) {
            $this->eventDispatcher->dispatch(
                LessonEvents::CLASSROOM_ENTERED,
                new LessonEvent($lesson, $user)
            );
        }
        $otToken = $this->lessonManager->generateOpenTokToken($lesson, $user);
        $lessonFileName = $lesson->getWhiteboardDir().'/lesson';
        $text = file_exists($lessonFileName)
            ? file_get_contents($lesson->getWhiteboardDir().'/lesson')
            : '';
        //dd($text);
        $lessonData = [
            'user'      => [
                'type' => in_array('ROLE_STUDENT', $user->getRoles())
                    ? 'student' : 'teacher',
                'name' => $user->getFirstName(),
            ],
            'pusherKey' => (string)$this->container->getParameter(
                'pusher_key'
            ),
            'lesson'    => [
                'startDate' => $lesson->getStartDate()->format('Y-m-d H:i:sO'),
                'length'    => $lesson->getPurchase()->getLessonLength(),
                'initText'  => $this->container->get('translator')->trans(
                    'classroom.initial_text', [], 'backend',
                    $lesson->getPurchase()->getStudent()->getUiLanguage()
                ),
                'id'        => $lesson->getId(),
                'saveUrl'   => $this->generateUrl(
                    'lesson.classroom.save',
                    ['lessonId' => $lessonId]
                ),
                'uploadUrl' => $this->generateUrl(
                    'lesson.classroom.upload',
                    ['lessonId' => $lessonId]
                ),
                'authUrl'   => $this->generateUrl(
                    'lesson.classroom.auth',
                    ['lessonId' => $lessonId]
                ),
                'text'      => $text,
            ],
            'ot'        => [
                'token'     => $otToken,
                'apiKey'    => (string)$this->container->getParameter(
                    'tokbox_api_key'
                ),
                'sessionId' => $lesson->getOpenTokSessionId(),
            ],
        ];

        $onlineStatusHelper = $this->container->get(
            'user.utils.online_status.helper'
        );
        $onlineStatus = $onlineStatusHelper->getUserOnlineStatus(
            $lesson->getPurchase()->getStudent()
        );
        return $this->render(
            ':Lesson:classroom.html.twig', [
                'onlineStatus' => $onlineStatus,
                'lesson'       => $lesson,
                'user'         => $user,
                'otToken'      => $otToken,
                'jsData'       => addslashes(json_encode($lessonData, JSON_UNESCAPED_UNICODE)),
            ]
        );
    }

    public function generateIcsFileAction(Request $request, $lessonId)
    {
        $user = $this->getUser();
        $lesson = $this->lessonManager->getLessonForClassroom($lessonId, $user);

        $stamp = Carbon::now('UTC');
        $startDate = Carbon::instance($lesson->getStartDate());
        $endDate = $startDate->copy()->addMinutes(
            $lesson->getPurchase()->getLessonLength()
        );

        $oUser = $lesson->getPurchase()->getStudent()->isEqualTo($user)
            ? $lesson->getPurchase()->getTeacher()
            : $lesson->getPurchase()->getStudent();

        $icsFileContent = $this->renderView(
            ':Lesson:calendar_event.ics.twig', [
                'user'        => $user,
                'participant' => $oUser,
                'language'    => $lesson->getPurchase()->getLanguage(),
                'lessonId'    => $lesson->getId(),
                'dateStamp'   => $stamp,
                'dateStart'   => $startDate,
                'dateEnd'     => $endDate,
            ]
        );

        $fileName = sprintf(
            'Langu lesson %s %s.ics', $lesson->getStartDate()->format('d-m-Y'),
            $lesson->getStartDate()->format('H_i')
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'text/calendar');
        $response->headers->set(
            'Content-Disposition', 'attachment; filename="'.$fileName.'";'
        );
        $response->setContent($icsFileContent);

        return $response;
    }

    public function finishAction(Request $request)
    {
        $user = $this->getUser();
        $lessonId = $request->request->get('lessonId');

        /* @var $lesson Lesson */
        $lesson = $this->lessonManager->find($lessonId);

        if (!$lesson) {
            throw $this->createNotFoundException('lesson.not_found');
        }

        if (!$this->isGranted(LessonVoterActions::FINISH, $lesson)) {
            $this->addFlash('error', 'lesson.finish.error');
            // event + flash because we can't finish this lesson, it has been finished already

            return $this->redirectToRoute('user.dashboard.current');
        }

        $this->lessonManager->finishLesson($lesson);
        $this->eventDispatcher->dispatch(
            LessonEvents::FINISHED, new LessonEvent($lesson, $user)
        );

        if ($lesson->getPurchase()->getStudent()->isEqualTo($user)) {
            return $this->redirectToRoute(
                'lesson.feedback.create', [
                    'lessonId' => $lesson->getId()
                ]
            );
        }

        return $this->redirectToRoute('user.dashboard.current');
    }

    public function manageResourcesAction(Request $request, $lessonId)
    {
        $user = $this->getUser();

        /* @var $lesson Lesson */
        $lesson = $this->lessonManager->find($lessonId);

        if (!$lesson) {
            return $this->createJsonResponse('lesson.not_found', [], 404);
        }

        if (!$this->isGranted(LessonVoterActions::MANAGE_RESOURCES, $lesson)) {
            return $this->createJsonResponse(
                'lesson.resources.access_denied', [], 403
            );
        }

        $resources = $this->container->get('media.resource_manager')
            ->getResourcesForUserAndLesson($user, $lesson);

        $form = $this->createForm(
            LessonResourcesFormType::class, $lesson, [
                'resources' => $resources,
                'action'    => $request->getUri(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lesson = $form->getData();
            $this->lessonManager->update($lesson);

            return $this->createJsonResponse('lesson.resources.updated');
        }

        return $this->render(
            ':User:_lesson_resources.html.twig', [
                'form' => $form->createView(),
            ]
        );
    }

    public function callAction(Request $request, $lessonId)
    {
        $user = $this->getUser();

        /* @var $lesson Lesson */
        $lesson = $this->lessonManager->find($lessonId);

        if ($lesson && $this->isGranted(LessonVoterActions::CALL, $lesson)) {
            if ($lesson->getPurchase()->getTeacher()->isEqualTo($user)) {
                $calledUser = $lesson->getPurchase()->getStudent();
            } else {
                $calledUser = $lesson->getPurchase()->getTeacher();
            }

            $templating = $this->container->get('templating');
            $partial = $templating->render(
                ':Lesson:buzz_message.html.twig', [
                    'lesson'   => $lesson,
                    'caller'   => $user,
                    'language' => $calledUser->getUiLanguage(),
                ]
            );

            $redisPublisher = $this->container->get('snc_redis.default');
            $redisPublisher->publish(
                $calledUser->getId(), json_encode(
                    [
                        'user' => $calledUser->getId(),
                        'type' => 'lesson:call',
                        'data' => [
                            'message' => $partial,
                        ]
                    ]
                )
            );

            return $this->createJsonResponse();
        }

        return $this->createJsonResponse('lesson.not_found', null, 404);
    }

    public function saveAction(Request $request, $lessonId)
    {
        $user = $this->getUser();
        /** @var null|Lesson $lesson */
        $lesson = $this->lessonManager->getLessonForClassroom($lessonId, $user);
        $text = $request->get('text', null);
        if (!$text) {
            throw new \LogicException('Parameter text must be set.');
        }
        $filesystem = new Filesystem();
        $dir = $lesson->getWhiteboardDir();
        if (!$dir) {
            $path = $this->container->getParameter('lessons_directory')
                    .'/'.intdiv($lesson->getId(), 10)
                    .'/'.$lesson->getId();
            $lesson->setWhiteboardDir($path);
            $this->lessonManager->update($lesson);
        }
        $filesystem->dumpFile($lesson->getWhiteboardDir().'/lesson', $text);
        return $this->createJsonResponse(null, [], 200);
    }

    public function uploadAction(Request $request, $lessonId)
    {
        $user = $this->getUser();
        /** @var null|Lesson $lesson */
        $lesson = $this->lessonManager->getLessonForClassroom($lessonId, $user);
        /** @var UploadedFile $file */
        $file = $request->files->get('file', null);
        if (!isset($file)) {
            throw new \LogicException('Parameter file must be set.');
        }
        $root = $this->get('kernel')->getRootDir().'/..';
        $fileName = md5(microtime()).'.jpg';
        $file->move($lesson->getWhiteboardDir(), $fileName);
        $uploadPath = str_replace(
            $root, '', $lesson->getWhiteboardDir().'/'.$fileName
        );
        return $this->createJsonResponse(null, ['url' => $uploadPath], 200);
    }

    public function authAction(Request $request, $lessonId)
    {
        $user = $this->getUser();
        /** @var null|Lesson $lesson */
        $lesson = $this->lessonManager->getLessonForClassroom($lessonId, $user);
        if (!$lesson || !$this->isGranted(LessonVoterActions::ENTER, $lesson)) {
            throw $this->createNotFoundException('lesson.not_found');
        }
        $socketId = $request->get('socket_id');
        $channelName = $request->get('channel_name');
        /** @var Pusher $pusher */
        $pusher = $this->container->get('pusher');
        return new Response(
            $pusher->presence_auth($channelName, $socketId, $user->getId())
        );
    }

    public function migrateAction()
    {
        /** @var GoogleMigrateManager $migrateManager */
        $migrateManager = $this->container->get('lesson.migrate_manager');
        $user = $this->getUser();
        $lessons = $migrateManager->getGoogleLessonsByUser($user);
        /** @var GoogleAuthToken $token */
        $token = $this->container->get('security.token_storage')->getToken();
        if (empty($lessons) || !$token instanceof GoogleAuthToken) {
            return new RedirectResponse('/');
        }
        $scopes = $this->container->getParameter('google_app_scopes');

        $data = [
            'accessToken' => $token->getRawToken(),
            'clientId'    => $this->container->getParameter('google_client_id'),
            'googleEmail' => $user->getGoogleEmail(),
            'lessons'     => $lessons,
            'scope'       => implode(' ', $scopes),
        ];
        return $this->render(
            ':Lesson:migrate.html.twig',
            ['jsData' => json_encode($data)]
        );
    }
}
