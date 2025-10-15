<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\PayPalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payment')]
#[IsGranted('ROLE_USER')]
class PaymentController extends AbstractController
{
    #[Route('/checkout/{orderId}', name: 'app_payment_checkout', methods: ['GET'])]
    public function checkout(int $orderId, OrderRepository $orderRepository, PayPalService $payPalService, EntityManagerInterface $entityManager): Response
    {
        // Récupérer la commande
        $order = $orderRepository->find($orderId);

        if (!$order) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }

        // Vérifier que l'utilisateur est propriétaire de la commande
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette commande.');
        }

        // Vérifier que la commande n'est pas déjà payée
        if ($order->isPaid()) {
            $this->addFlash('warning', 'Cette commande a déjà été payée.');
            return $this->redirectToRoute('app_user_orders');
        }

        // Recalculer les totaux pour s'assurer qu'ils sont corrects
        $order->calculateTotals();
        $entityManager->flush();

        // Créer le paiement PayPal
        $payment = $payPalService->createPayment($order);

        return $this->render('payment/checkout.html.twig', [
            'order' => $order,
            'payment' => $payment,
        ]);
    }

    #[Route('/success', name: 'app_payment_success', methods: ['GET'])]
    public function success(Request $request, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
    {
        $orderId = $request->query->get('order_id');

        if (!$orderId) {
            $this->addFlash('error', 'Informations de commande manquantes.');
            return $this->redirectToRoute('app_user_orders');
        }

        $order = $orderRepository->find($orderId);

        if (!$order || $order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Commande non trouvée ou accès non autorisé.');
        }

        // Valider automatiquement le paiement (simulation)
        $order->setStatus('paid');
        $order->setPaypalTransactionId('PAY-' . uniqid());
        $order->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', 'Paiement effectué avec succès ! Votre commande a été confirmée.');

        return $this->redirectToRoute('app_payment_success_page', ['orderId' => $order->getId()]);
    }

    #[Route('/cancel', name: 'app_payment_cancel', methods: ['GET'])]
    public function cancel(Request $request, OrderRepository $orderRepository): Response
    {
        $orderId = $request->query->get('order_id');

        if ($orderId) {
            $order = $orderRepository->find($orderId);
            if ($order && $order->getUser() === $this->getUser()) {
                $this->addFlash('warning', 'Paiement annulé. Vous pouvez réessayer quand vous le souhaitez.');
                return $this->redirectToRoute('app_payment_checkout', ['orderId' => $order->getId()]);
            }
        }

        $this->addFlash('warning', 'Paiement annulé.');
        return $this->redirectToRoute('app_user_orders');
    }

    #[Route('/success-page/{orderId}', name: 'app_payment_success_page', methods: ['GET'])]
    public function successPage(int $orderId, OrderRepository $orderRepository): Response
    {
        // Récupérer la commande
        $order = $orderRepository->find($orderId);

        if (!$order) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }

        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette commande.');
        }

        return $this->render('payment/success.html.twig', [
            'order' => $order,
        ]);
    }
}
