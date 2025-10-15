<?php

namespace App\EventListener;

use App\Event\BookUpdateEvent;
use App\Repository\CartItemRepository;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: BookUpdateEvent::NAME)]
class CartNotificationListener
{
    public function __construct(
        private CartItemRepository $cartItemRepository,
        private NotificationService $notificationService
    ) {}

    public function __invoke(BookUpdateEvent $event): void
    {
        $book = $event->getBook();
        
        // Trouver tous les paniers contenant ce livre
        $cartItems = $this->cartItemRepository->findByBook($book);
        
        foreach ($cartItems as $cartItem) {
            $user = $cartItem->getCart()->getUser();
            $notification = $this->createNotification($event, $cartItem);
            
            // Envoyer la notification
            $this->notificationService->sendNotification($user, $notification);
        }
    }

    private function createNotification(BookUpdateEvent $event, $cartItem): array
    {
        $book = $event->getBook();
        $notification = [
            'type' => 'book_update',
            'book_id' => $book->getId(),
            'book_title' => $book->getTitle(),
            'cart_item_id' => $cartItem->getId(),
            'message' => '',
            'actions' => []
        ];

        if ($event->hasPriceChanged()) {
            $oldPrice = $event->getOldPrice();
            $newPrice = $event->getNewPrice();
            $priceDiff = $newPrice - $oldPrice;
            
            if ($priceDiff > 0) {
                $notification['message'] = "Le prix de « {$book->getTitle()} » a augmenté de " . number_format($priceDiff, 2) . " $ CAD";
                $notification['type'] = 'price_increase';
            } else {
                $notification['message'] = "Le prix de « {$book->getTitle()} » a diminué de " . number_format(abs($priceDiff), 2) . " $ CAD";
                $notification['type'] = 'price_decrease';
            }
            
            $notification['actions'][] = [
                'label' => 'Voir le panier',
                'url' => '/cart',
                'class' => 'btn-primary'
            ];
        }

        if ($event->hasStockChanged()) {
            $newStock = $event->getNewStock();
            
            if ($newStock === 0) {
                $notification['message'] = "« {$book->getTitle()} » est maintenant en rupture de stock";
                $notification['type'] = 'out_of_stock';
                $notification['actions'][] = [
                    'label' => 'Retirer du panier',
                    'url' => '/cart/remove/' . $cartItem->getId(),
                    'class' => 'btn-danger',
                    'method' => 'POST'
                ];
            } elseif ($newStock <= 5) {
                $notification['message'] = "Il ne reste que {$newStock} exemplaire(s) de « {$book->getTitle()} »";
                $notification['type'] = 'low_stock';
                $notification['actions'][] = [
                    'label' => 'Acheter maintenant',
                    'url' => '/cart',
                    'class' => 'btn-warning'
                ];
            }
        }

        return $notification;
    }
}
