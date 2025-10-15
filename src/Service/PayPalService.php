<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PayPalService
{
    private string $clientId;
    private string $clientSecret;
    private string $environment;

    public function __construct(ParameterBagInterface $params = null)
    {
        // Pour les tests, utiliser des valeurs par défaut
        $this->clientId = 'test_client_id';
        $this->clientSecret = 'test_client_secret';
        $this->environment = 'sandbox';
    }

    public function createPayment(Order $order): array
    {
        // Créer l'URL de paiement PayPal directe
        $amount = number_format($order->getTotal(), 2, '.', '');
        $itemName = 'Commande #' . $order->getOrderNumber();
        $returnUrl = 'http://localhost:8000/payment/success?order_id=' . $order->getId();
        $cancelUrl = 'http://localhost:8000/payment/cancel?order_id=' . $order->getId();

        // URL PayPal pour paiement direct (mode test)
        $paypalUrl = "https://www.sandbox.paypal.com/cgi-bin/webscr?" . http_build_query([
            'cmd' => '_xclick',
            'business' => 'test@paypal.com',
            'item_name' => $itemName,
            'amount' => $amount,
            'currency_code' => 'CAD',
            'return' => $returnUrl,
            'cancel_return' => $cancelUrl,
            'notify_url' => $returnUrl,
            'no_shipping' => '1',
            'no_note' => '1',
            'charset' => 'UTF-8'
        ]);

        return [
            'payment_url' => $paypalUrl,
            'amount' => $amount,
            'currency' => 'CAD'
        ];
    }


}
