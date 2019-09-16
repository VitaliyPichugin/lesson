<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Listener;

use Carbon\Carbon;
use IntlDateFormatter;
use LessonBundle\Event\LessonEvent;
use LessonBundle\Event\LessonEvents;
use Swift_Message;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Description of LessonListener
 *
 */
class LessonSubscriber implements EventSubscriberInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     * 
     */
    private $container;

    /**
     *
     * @var TranslatorInterface
     */
    private $translator;
    private $mailer;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->mailer = $container->get('mailer');
        $this->translator = $container->get('translator');
    }

    public static function getSubscribedEvents()
    {
        return [
            LessonEvents::CLASSROOM_INITIALIZED => 'onLessonInitialized',
            LessonEvents::CANCELLED => 'onLessonCancelled',
            LessonEvents::BOOKED => 'onLessonBooked',
        ];
    }
    
    public function onLessonInitialized(LessonEvent $event)
    {
        $lesson = $event->getLesson();
        $purchase = $lesson->getPurchase();
        $lessonId = $lesson->getId();
        $studentId = $purchase->getStudent()->getId();
        
        $router = $this->container->get('router');
        $route = $router->generate('lesson.classroom.enter', [
            'lessonId' => $lessonId,
        ]);
        $redisPublisher = $this->container->get('snc_redis.default');
        $redisPublisher->publish($studentId, json_encode([
            'user' => $studentId,
            'type' => 'lesson:init',
            'data' => [
                'lesson' => $lessonId,
                'url' => $route,
            ]
        ]));
    }

    public function onLessonBooked(LessonEvent $event)
    {
        $lesson = $event->getLesson();
        $purchase = $lesson->getPurchase();
        $student = $purchase->getStudent();
        $teacher = $purchase->getTeacher();
        
        $language = $purchase->getLanguage();
        $em = $this->container->get('doctrine')->getManager();
        $language->setTranslatableLocale($teacher->getUiLanguage());
        $em->refresh($language);

        $templating = $this->container->get('templating');

        // TEACHER MAIL
        $tDt = Carbon::instance($lesson->getStartDate())->setTimezone($teacher->getTimezone());

        $lessonInfo = null;
        
        if(null !== $purchase->getPaymentTransaction()) {
            $lessonInfo['%lesson%'] = $lesson->getOrdinal();
            $lessonInfo['%totalLessons%'] = $purchase->getLessons();
        }
        
        $params = [
            'studentName' => $student->getFullName(),
            'language' => $language,
            'lessonInfo' => $lessonInfo,
            'recipientLocale' => $teacher->getLocale(),
            'recipientLanguage' => $teacher->getUiLanguage(),
            'recipientTime' => $tDt,
        ];

        $tContent = $templating->render(':Mails:teacher_booked_lesson.html.twig', $params);
        $tSubject = $this->translator->trans('teacher_booked_lesson_subject', [], 'mail', $teacher->getUiLanguage());

        $message = new Swift_Message();
        $message->setContentType('text/html');
        $message->setFrom('langu@heylangu.com', 'Team Langu');
        $message->setSubject($tSubject);
        
        foreach ($teacher->getEmails() as $email) {
            $message->addTo($email, $teacher->getFullName());
        }

        $message->setBody($tContent, 'text/html');

        $this->mailer->send($message);
        
        // STUDENT MAIL
        $sDt = Carbon::instance($lesson->getStartDate())->setTimezone($student->getTimezone());
        
        $sParams = [
            'teacherName' => $teacher->getFirstName(),
            'language' => $language,
            'recipientLocale' => $student->getLocale(),
            'recipientLanguage' => $student->getUiLanguage(),
            'recipientTime' => $sDt
        ];

        $dateFormatter = new IntlDateFormatter($student->getLocale(), IntlDateFormatter::SHORT, IntlDateFormatter::NONE, $student->getTimezone());
        $timeFormatter = new IntlDateFormatter($student->getLocale(), IntlDateFormatter::NONE, IntlDateFormatter::SHORT, $student->getTimezone());

        if($purchase->getLessons() > 1) {
            $sContent = $templating->render(':Mails:student_book_lesson_package.html.twig', $sParams);
        } else {        
            $sParams['teacherUsername'] = $teacher->getUsername();
            $sContent = $templating->render(':Mails:student_book_lesson.html.twig', $sParams);
        }
        $sSubject = $this->translator->trans('student_book_lesson_subject', [
            '%teacherName%' => $teacher->getFirstName(),
            '%recipientDate%' => $dateFormatter->format($sDt),
            '%recipientTime%' => $timeFormatter->format($sDt),
        ], 'mail', $student->getUiLanguage());        
        $studentMessage = new Swift_Message();
        $studentMessage->setContentType('text/html');
        $studentMessage->setFrom('langu@heylangu.com', 'Team Langu');
        $studentMessage->setSubject($sSubject);
        
        foreach ($student->getEmails() as $email) {
            $studentMessage->addTo($email, $student->getFullName());
        }

        $studentMessage->setBody($sContent, 'text/html');

        $this->mailer->send($studentMessage);
    }

    public function onLessonCancelled(LessonEvent $event)
    {
        $user = $event->getAuthor();
        $lesson = $event->getLesson();

        if ($user->isEqualTo($lesson->getPurchase()->getStudent())) {
            // STUDENT
            $purchase = $lesson->getPurchase();
            $teacher = $purchase->getTeacher();

            $studentName = $user->getFirstName();
            $teacherName = $teacher->getFirstName();
            $language = $purchase->getLanguage();
            $lessonTime = $lesson->getStartDate();
            $teacherTime = Carbon::instance($lessonTime)->setTimezone($teacher->getTimezone());
            $teacherLocale = $teacher->getLocale();
            $teacherLanguage = $teacher->getUiLanguage();

            $templating = $this->container->get('templating');
            $content = $templating->render(':Mails:student_cancel_lesson.html.twig', [
                'studentName' => $studentName,
                'teacherName' => $teacherName,
                'language' => $language,
                'teacherTime' => $teacherTime,
                'teacherLocale' => $teacherLocale,
                'teacherLanguage' => $teacherLanguage,
            ]);

            $subject = $this->translator->trans('student_cancel_lesson_subject', ['%studentName%' => $studentName], 'mail', $teacherLanguage);

            $recipient = $teacher;
        }

        if ($user->isEqualTo($lesson->getPurchase()->getTeacher())) {
            // TEACHER
            $purchase = $lesson->getPurchase();
            $student = $purchase->getStudent();

            $teacherName = $user->getFirstName();
            $studentName = $student->getFirstName();
            $language = $purchase->getLanguage();
            $lessonTime = $lesson->getStartDate();
            $studentTime = Carbon::instance($lessonTime)->setTimezone($student->getTimezone());
            $studentLocale = $student->getLocale();
            $studentLanguage = $student->getUiLanguage();

            $templating = $this->container->get('templating');

            if ($lesson->isTrial()) {
                $template = ':Mails:teacher_cancel_trial.html.twig';
                $subject = $this->translator->trans('teacher_cancel_trial_subject', [], 'mail', $studentLanguage);
            } else {
                $template = ':Mails:teacher_cancel_lesson.html.twig';
                $subject = $this->translator->trans('teacher_cancel_lesson_subject', [], 'mail', $studentLanguage);
            }

            $content = $templating->render($template, [
                'studentName' => $studentName,
                'teacherName' => $teacherName,
                'language' => $language,
                'studentTime' => $studentTime,
                'studentLocale' => $studentLocale,
                'studentLanguage' => $studentLanguage,
            ]);

            $recipient = $student;
        }

        if (isset($subject) && isset($content)) {
            $message = new Swift_Message();
            $message->setContentType('text/html');
            $message->setFrom('langu@heylangu.com', 'Team Langu');
            $message->setSubject($subject);
            foreach ($recipient->getEmails() as $email) {
                $message->addTo($email, $recipient->getFullName());
            }

            $message->setBody($content, 'text/html');

            $this->mailer->send($message);
        }
    }

}
