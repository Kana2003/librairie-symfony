<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

class NotificationService
{
    public function __construct(
        private RequestStack $requestStack
    ) {
        // Utiliser RequestStack pour accéder à la session
    }

    public function sendNotification(User $user, array $notification): void
    {
        // En production, vous pourriez stocker les notifications en base de données
        // ou utiliser un système de messagerie comme Redis/RabbitMQ

        // Pour cette implémentation, nous utilisons la session
        $session = $this->requestStack->getSession();
        $notifications = $session->get('user_notifications_' . $user->getId(), []);
        $notifications[] = array_merge($notification, [
            'id' => uniqid(),
            'created_at' => new \DateTimeImmutable(),
            'read' => false
        ]);

        $session->set('user_notifications_' . $user->getId(), $notifications);
    }

    public function getUserNotifications(User $user): array
    {
        $session = $this->requestStack->getSession();
        return $session->get('user_notifications_' . $user->getId(), []);
    }

    public function markAsRead(User $user, string $notificationId): void
    {
        $session = $this->requestStack->getSession();
        $notifications = $this->getUserNotifications($user);

        foreach ($notifications as &$notification) {
            if ($notification['id'] === $notificationId) {
                $notification['read'] = true;
                break;
            }
        }

        $session->set('user_notifications_' . $user->getId(), $notifications);
    }

    public function getUnreadCount(User $user): int
    {
        $notifications = $this->getUserNotifications($user);
        return count(array_filter($notifications, fn($n) => !$n['read']));
    }

    public function clearNotifications(User $user): void
    {
        $session = $this->requestStack->getSession();
        $session->remove('user_notifications_' . $user->getId());
    }
}
