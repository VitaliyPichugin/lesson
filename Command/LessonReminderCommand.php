<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Command;

use Carbon\Carbon;
use DateTime;
use Exception;
use LessonBundle\Entity\Lesson;
use LessonBundle\Model\GoogleLessonManager;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Description of LessonReminderCommand
 *
 */
class LessonReminderCommand extends Command 
{    
    /**
     * @var GoogleLessonManager
     */
    protected $lessonManager;

    protected $mailer;
    
    /**
     * @var SymfonyStyle
     */
    protected $io;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     *
     * @var TranslatorInterface
     */
    protected $translator;
    
    /**
     *
     * @var DateTime
     */
    protected $date;
    
    protected function configure() {
        parent::configure();
        
        $this
                ->setProcessTitle('Lessons reminders mailing script')
                ->setName('lesson:upcoming:reminder')
                ->setDescription('Command for sending emails to users about upcoming lessons');
    }
    
    protected function initialize(InputInterface $input, OutputInterface $output) 
    {
        $this->lessonManager = $this->getContainer()->get('lesson.lesson_manager');
        $this->mailer = $this->getContainer()->get('mailer');
        $this->logger = $this->getContainer()->get('logger');
        $this->translator = $this->getContainer()->get('translator');
        $this->date = Carbon::now('UTC');
        
        $this->io = new SymfonyStyle($input, $output);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) 
    {
        $this->io->title('Lesson reminders queue');
        $this->io->section('Fetching upcoming lessons');

        $upcomingLessons = $this->lessonManager->getUpcomingLessons($this->date);

        if(empty($upcomingLessons)) {
            $this->io->comment('No pending lessons found');
            $this->io->success('Lesson reminder queue processed');
            return;
        }
        
        $this->io->section('Processing pending reminders');
                
        $max = count($upcomingLessons);
        $this->io->progressStart($max);
        
        /* @var $lesson Lesson */
        foreach($upcomingLessons as $lesson) {
            try {
                $this->sendTeacherEmail($lesson);
                $this->sendStudentEmail($lesson);
                
                $lesson->setReminderAt($this->date);
                $this->lessonManager->update($lesson, false);
            } catch(Exception $e) {
                $this->logger->error('lesson.reminder.error', [
                    'error' => $e->getMessage(),
                    'stack' => $e->getTrace(),
                    'lesson_id' => $lesson->getId(),
                ]);
                
                throw $e;
            }
            
            $this->io->progressAdvance();
        }
        
        $this->lessonManager->getManager()->flush();
        $this->io->progressFinish();
        
        $this->io->success('Lesson reminder queue processed');
    }
    
    private function sendTeacherEmail(Lesson $lesson)
    {
        $purchase = $lesson->getPurchase();
        $student = $purchase->getStudent();
        $teacher = $purchase->getTeacher();
        $language = $purchase->getLanguage();
        
        $templating = $this->getContainer()->get('templating');

        $dt = Carbon::instance($lesson->getStartDate())->setTimezone($teacher->getTimezone());
        $params = [
            'studentName' => $student->getFirstName(),
            'language' => $language,
            'recipientLocale' => $teacher->getLocale(),
            'recipientLanguage' => $teacher->getUiLanguage(),
            'recipientTime' => $dt,
        ];

        $content = $templating->render(':Mails:teacher_lesson_reminder.html.twig', $params);
        $subject = $this->translator->trans('lesson_reminder_subject', [], 'mail', $teacher->getUiLanguage());

        $message = new Swift_Message();
        $message->setContentType('text/html');
        $message->setFrom('langu@heylangu.com', 'Team Langu');
        $message->setSubject($subject);
        
        foreach ($teacher->getEmails() as $email) {
            $message->addTo($email, $teacher->getFullName());
        }

        $message->setBody($content, 'text/html');

        $this->mailer->send($message);
    }
    
    private function sendStudentEmail(Lesson $lesson)
    {
        $purchase = $lesson->getPurchase();
        $student = $purchase->getStudent();
        $teacher = $purchase->getTeacher();
        $language = $purchase->getLanguage();
        
        $templating = $this->getContainer()->get('templating');

        $dt = Carbon::instance($lesson->getStartDate())->setTimezone($student->getTimezone());
        $params = [
            'teacherName' => $teacher->getFirstName(),
            'language' => $language,
            'recipientLocale' => $student->getLocale(),
            'recipientLanguage' => $student->getUiLanguage(),
            'recipientTime' => $dt,
        ];

        $content = $templating->render(':Mails:student_lesson_reminder.html.twig', $params);
        $subject = $this->translator->trans('lesson_reminder_subject', [], 'mail', $student->getUiLanguage());

        $message = new Swift_Message();
        $message->setContentType('text/html');
        $message->setFrom('langu@heylangu.com', 'Team Langu');
        $message->setSubject($subject);
        
        foreach ($student->getEmails() as $email) {
            $message->addTo($email, $student->getFullName());
        }

        $message->setBody($content, 'text/html');

        $this->mailer->send($message);
    }
}