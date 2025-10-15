<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\BookRepository;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart_index', methods: ['GET'])]
    public function index(CartRepository $cartRepository): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findActiveCartByUser($user);

        if (!$cart) {
            $cart = $cartRepository->createCartForUser($user);
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Book $book, Request $request, CartRepository $cartRepository, CartItemRepository $cartItemRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findActiveCartByUser($user);

        if (!$cart) {
            $cart = $cartRepository->createCartForUser($user);
        }

        // Check if book is already in cart
        $existingCartItem = $cartItemRepository->findCartItemByBookAndCart($book, $cart);

        if ($existingCartItem) {
            $existingCartItem->incrementQuantity();
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setBook($book);
            $cartItem->setPrice($book->getPrice());
            $cartItem->setQuantity(1);

            $entityManager->persist($cartItem);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Le livre a été ajouté au panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(CartItem $cartItem, Request $request, EntityManagerInterface $entityManager): Response
    {
        $quantity = (int) $request->request->get('quantity');

        if ($quantity > 0) {
            $cartItem->setQuantity($quantity);
            $entityManager->flush();
            $this->addFlash('success', 'Quantité mise à jour.');
        } else {
            $entityManager->remove($cartItem);
            $entityManager->flush();
            $this->addFlash('success', 'Article supprimé du panier.');
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(CartItem $cartItem, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($cartItem);
        $entityManager->flush();

        $this->addFlash('success', 'Article supprimé du panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(CartRepository $cartRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findActiveCartByUser($user);

        if ($cart) {
            // Supprimer tous les éléments du panier
            foreach ($cart->getCartItems() as $cartItem) {
                $entityManager->remove($cartItem);
            }
            $entityManager->flush();
        }

        $this->addFlash('success', 'Le panier a été vidé.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/checkout', name: 'app_cart_checkout', methods: ['GET'])]
    public function checkout(CartRepository $cartRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findActiveCartByUser($user);

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart_index');
        }

        // Créer une nouvelle commande à partir du panier
        $order = new \App\Entity\Order();
        $order->setUser($user);

        // Convertir les éléments du panier en éléments de commande
        foreach ($cart->getCartItems() as $cartItem) {
            $orderItem = new \App\Entity\OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setBook($cartItem->getBook());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setPrice($cartItem->getPrice());
            // Le sous-total est calculé automatiquement par la méthode getSubtotal()
            
            $entityManager->persist($orderItem);
            $order->addOrderItem($orderItem);
        }

        // Calculer les totaux APRÈS avoir ajouté tous les éléments
        $order->calculateTotals();
        
        $entityManager->persist($order);
        $entityManager->flush();

        // Vider le panier après création de la commande
        foreach ($cart->getCartItems() as $cartItem) {
            $entityManager->remove($cartItem);
        }
        $entityManager->flush();

        $this->addFlash('success', 'Commande créée avec succès. Redirection vers le paiement.');

        // Rediriger vers la page de paiement
        return $this->redirectToRoute('app_payment_checkout', ['orderId' => $order->getId()]);
    }
}
