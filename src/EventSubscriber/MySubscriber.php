<?php


namespace App\EventSubscriber;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class MySubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {
    private $tokenStorage;
    private $em;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $em
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
    }

    public function onRequest(RequestEvent $event) {
        if (!$token = $this->tokenStorage->getToken()) {
            return;
        }

        if ($event->getRequest()->attributes->get('_route') == 'login') {
            //$user = $this->
            $event->setResponse(new RedirectResponse('error'));
        }
    }

    public static function getSubscribedEvents() {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }
}