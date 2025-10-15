<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class DirectPurchaseController extends AbstractController
{
    #[Route('/buy/{id}', name: 'app_direct_purchase', methods: ['GET', 'POST'])]
    public function directPurchase(Book $book, Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        // Vérifier si le livre est disponible
        if (!$book->isAvailable()) {
            $this->addFlash('error', 'Ce livre n\'est plus disponible.');
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $quantity = (int) $request->request->get('quantity', 1);
            
            // Vérifier la quantité disponible
            if ($quantity > $book->getStock()) {
                $this->addFlash('error', 'La quantité demandée n\'est pas disponible.');
                return $this->redirectToRoute('app_direct_purchase', ['id' => $book->getId()]);
            }

            // Stocker les informations d'achat en session pour redirection vers connexion
            $session->set('pending_purchase', [
                'book_id' => $book->getId(),
                'quantity' => $quantity,
                'total' => $book->getPrice() * $quantity,
                'total_with_tax' => ($book->getPrice() * $quantity) * 1.14975
            ]);

            // Rediriger vers la page de connexion avec message
            $this->addFlash('info', 'Pour finaliser votre achat, veuillez vous connecter ou créer un compte.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('direct_purchase/index.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/guest-cart', name: 'app_guest_cart', methods: ['GET'])]
    public function guestCart(SessionInterface $session, BookRepository $bookRepository): Response
    {
        $guestCart = $session->get('guest_cart', []);
        $cartItems = [];

        foreach ($guestCart as $bookId => $quantity) {
            $book = $bookRepository->find($bookId);
            if ($book && $book->isAvailable()) {
                $cartItems[] = [
                    'book' => $book,
                    'quantity' => $quantity,
                    'subtotal' => $book->getPrice() * $quantity
                ];
            }
        }

        $total = array_sum(array_column($cartItems, 'subtotal'));
        $totalWithTax = $total * 1.14975;

        return $this->render('direct_purchase/guest_cart.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
            'totalWithTax' => $totalWithTax,
        ]);
    }

    #[Route('/guest-cart/add/{id}', name: 'app_guest_cart_add', methods: ['POST'])]
    public function addToGuestCart(Book $book, Request $request, SessionInterface $session): Response
    {
        if (!$book->isAvailable()) {
            $this->addFlash('error', 'Ce livre n\'est plus disponible.');
            return $this->redirectToRoute('app_home');
        }

        $quantity = (int) $request->request->get('quantity', 1);
        
        if ($quantity > $book->getStock()) {
            $this->addFlash('error', 'La quantité demandée n\'est pas disponible.');
            return $this->redirectToRoute('app_home');
        }

        $guestCart = $session->get('guest_cart', []);
        $bookId = $book->getId();

        if (isset($guestCart[$bookId])) {
            $guestCart[$bookId] += $quantity;
        } else {
            $guestCart[$bookId] = $quantity;
        }

        $session->set('guest_cart', $guestCart);
        $this->addFlash('success', 'Livre ajouté au panier invité.');

        return $this->redirectToRoute('app_guest_cart');
    }

    #[Route('/guest-cart/remove/{id}', name: 'app_guest_cart_remove', methods: ['POST'])]
    public function removeFromGuestCart(Book $book, SessionInterface $session): Response
    {
        $guestCart = $session->get('guest_cart', []);
        $bookId = $book->getId();

        if (isset($guestCart[$bookId])) {
            unset($guestCart[$bookId]);
            $session->set('guest_cart', $guestCart);
            $this->addFlash('success', 'Livre retiré du panier.');
        }

        return $this->redirectToRoute('app_guest_cart');
    }

    #[Route('/guest-cart/clear', name: 'app_guest_cart_clear', methods: ['POST'])]
    public function clearGuestCart(SessionInterface $session): Response
    {
        $session->remove('guest_cart');
        $this->addFlash('success', 'Panier vidé.');
        return $this->redirectToRoute('app_home');
    }
}
