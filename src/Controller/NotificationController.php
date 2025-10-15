<?php

namespace App\Controller;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('/', name: 'app_notifications', methods: ['GET'])]
    public function index(NotificationService $notificationService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $notifications = $notificationService->getUserNotifications($user);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/mark-read/{id}', name: 'app_notification_mark_read', methods: ['POST'])]
    public function markAsRead(string $id, NotificationService $notificationService): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $notificationService->markAsRead($user, $id);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/unread-count', name: 'app_notification_unread_count', methods: ['GET'])]
    public function getUnreadCount(NotificationService $notificationService): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $count = $notificationService->getUnreadCount($user);

        return new JsonResponse(['count' => $count]);
    }

    #[Route('/clear', name: 'app_notification_clear', methods: ['POST'])]
    public function clearAll(NotificationService $notificationService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $notificationService->clearNotifications($user);

        $this->addFlash('success', 'Toutes les notifications ont été supprimées.');

        return $this->redirectToRoute('app_notifications');
    }
}
