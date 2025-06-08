<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginSuccessSubscriber implements EventSubscriberInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $redirectUrl = $this->router->generate('app_admin_dashboard');
        } elseif (in_array('ROLE_MEDECIN', $user->getRoles(), true)) {
            $redirectUrl = $this->router->generate('app_medecin_dashboard');
        } elseif (in_array('ROLE_PATIENT', $user->getRoles(), true)) {
            $redirectUrl = $this->router->generate('app_patient_dashboard');
        } else {
            $redirectUrl = $this->router->generate('app_login');
        }

        $event->getRequest()->getSession()->set('_security.main.target_path', $redirectUrl);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        ];
    }
}
