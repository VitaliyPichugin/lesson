<?php

namespace LessonBundle\Listener;

use LessonBundle\Model\AbstractLessonManager;
use LessonBundle\Model\GoogleLessonManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\TranslatorInterface;
use UserBundle\Entity\StudentUser;

/**
 * Description of FeedbackListener
 *
 */
class FeedbackListener
{

    /**
     * @var GoogleLessonManager
     */
    private $lessonManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Router 
     */
    private $router;

    /**
     * @var TokenStorage 
     */
    private $tokenStorage;

    public function __construct(AbstractLessonManager $lessonManager, TokenStorage $tokenStorage, TranslatorInterface $translator, Router $router)
    {
        $this->lessonManager = $lessonManager;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->router = $router;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        if (!$controller[0] instanceof Controller) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (in_array($route, ['lesson.feedback.create', 'lesson.finish'])) {
            return;
        }

        if (!$this->tokenStorage->getToken() || !($user = $this->tokenStorage->getToken()->getUser()) instanceof StudentUser) {
            return;
        }

        if (null !== $lessonId = $this->lessonManager->hasFeedbackMissingLessons($user)) {

            $path = $this->router->generate('lesson.feedback.create', ['lessonId' => $lessonId]);
            $event->setController(function() use ($path) {
                return new RedirectResponse($path);
            });
        }
    }

}
