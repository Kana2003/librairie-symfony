<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class SessionTimeoutListener
{
    private const TIMEOUT_SECONDS = 120; // 2 minutes
    private const TIMEOUT_ATTRIBUTE = 'last_activity';

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface $router
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        // Vérifier si l'utilisateur est connecté
        try {
            $token = $this->tokenStorage->getToken();
            if (!$token || !$token->getUser()) {
                return;
            }
        } catch (AuthenticationCredentialsNotFoundException $e) {
            return;
        }

        // Routes à exclure du timeout
        $excludedRoutes = [
            'app_login',
            'app_register',
            'app_logout',
            'app_home'
        ];

        $currentRoute = $request->get('_route');
        if (in_array($currentRoute, $excludedRoutes)) {
            return;
        }

        $lastActivity = $session->get(self::TIMEOUT_ATTRIBUTE);
        $currentTime = time();

        if ($lastActivity && ($currentTime - $lastActivity) > self::TIMEOUT_SECONDS) {
            // Session expirée, déconnecter l'utilisateur
            $this->tokenStorage->setToken(null);
            $session->invalidate();

            // Rediriger vers la page de connexion avec un message
            $response = new RedirectResponse($this->router->generate('app_login'));
            $event->setResponse($response);

            return;
        }

        // Mettre à jour le timestamp de dernière activité
        $session->set(self::TIMEOUT_ATTRIBUTE, $currentTime);
    }
}
