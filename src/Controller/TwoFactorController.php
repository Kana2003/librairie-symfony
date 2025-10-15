<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\OtpService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TwoFactorController extends AbstractController
{
    #[Route('/2fa/request', name: 'app_2fa_request', methods: ['GET', 'POST'])]
    public function requestOtp(Request $request, UserRepository $userRepository, OtpService $otpService, SessionInterface $session): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $email, 'isActive' => true, 'isDeleted' => false]);

            if ($user) {
                $otp = $otpService->generateOtp();
                
                // Stocker l'OTP en session (en production, utilisez Redis ou base de données)
                $session->set('pending_2fa_user_id', $user->getId());
                $session->set('pending_2fa_otp', $otp);
                $session->set('pending_2fa_timestamp', time());

                // Envoyer l'email OTP
                $otpService->sendOtpEmail($user, $otp);

                $this->addFlash('success', 'Un code de vérification a été envoyé à votre adresse email.');

                return $this->redirectToRoute('app_2fa_verify');
            } else {
                $this->addFlash('error', 'Aucun compte actif trouvé avec cette adresse email.');
            }
        }

        return $this->render('security/2fa_request.html.twig');
    }

    #[Route('/2fa/verify', name: 'app_2fa_verify', methods: ['GET', 'POST'])]
    public function verifyOtp(Request $request, UserRepository $userRepository, OtpService $otpService, SessionInterface $session, EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher): Response
    {
        $userId = $session->get('pending_2fa_user_id');
        $storedOtp = $session->get('pending_2fa_otp');
        $otpTimestamp = $session->get('pending_2fa_timestamp');

        if (!$userId || !$storedOtp || !$otpTimestamp) {
            $this->addFlash('error', 'Session de vérification invalide. Veuillez recommencer.');
            return $this->redirectToRoute('app_2fa_request');
        }

        if ($otpService->isOtpExpired($otpTimestamp)) {
            $this->clear2faSession($session);
            $this->addFlash('error', 'Le code de vérification a expiré. Veuillez en demander un nouveau.');
            return $this->redirectToRoute('app_2fa_request');
        }

        if ($request->isMethod('POST')) {
            $submittedOtp = $request->request->get('otp');

            if ($submittedOtp === $storedOtp) {
                $user = $userRepository->find($userId);
                
                if ($user) {
                    // Mettre à jour la dernière connexion
                    $user->setLastLoginAt(new \DateTimeImmutable());
                    $entityManager->flush();

                    // Créer le token d'authentification
                    $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
                    
                    // Déclencher l'événement de connexion
                    $event = new InteractiveLoginEvent($request, $token);
                    $eventDispatcher->dispatch($event, SecurityEvents::INTERACTIVE_LOGIN);

                    // Nettoyer la session 2FA
                    $this->clear2faSession($session);

                    $this->addFlash('success', 'Connexion réussie ! Bienvenue ' . $user->getFirstName() . '.');

                    return $this->redirectToRoute('app_home');
                }
            } else {
                $this->addFlash('error', 'Code de vérification incorrect. Veuillez réessayer.');
            }
        }

        return $this->render('security/2fa_verify.html.twig');
    }

    #[Route('/2fa/resend', name: 'app_2fa_resend', methods: ['POST'])]
    public function resendOtp(Request $request, UserRepository $userRepository, OtpService $otpService, SessionInterface $session): Response
    {
        $userId = $session->get('pending_2fa_user_id');
        
        if (!$userId) {
            $this->addFlash('error', 'Session invalide.');
            return $this->redirectToRoute('app_2fa_request');
        }

        $user = $userRepository->find($userId);
        
        if ($user) {
            $otp = $otpService->generateOtp();
            
            // Mettre à jour l'OTP en session
            $session->set('pending_2fa_otp', $otp);
            $session->set('pending_2fa_timestamp', time());

            // Envoyer le nouvel email OTP
            $otpService->sendOtpEmail($user, $otp);

            $this->addFlash('success', 'Un nouveau code de vérification a été envoyé.');
        }

        return $this->redirectToRoute('app_2fa_verify');
    }

    private function clear2faSession(SessionInterface $session): void
    {
        $session->remove('pending_2fa_user_id');
        $session->remove('pending_2fa_otp');
        $session->remove('pending_2fa_timestamp');
    }
}
