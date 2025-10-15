<?php

namespace App\Controller;

use App\Entity\Book;
use App\Event\BookUpdateEvent;
use App\Form\BookType;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/book')]
#[IsGranted('ROLE_ADMIN')]
class AdminBookController extends AbstractController
{
    #[Route('/', name: 'admin_book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        
        if ($search) {
            $books = $bookRepository->searchBooks($search);
        } else {
            $books = $bookRepository->findAll();
        }

        return $this->render('admin_book/index.html.twig', [
            'books' => $books,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'admin_book_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('books_directory'),
                    $newFilename
                );
                $book->setImage($newFilename);
            }

            $entityManager->persist($book);
            $entityManager->flush();

            $this->addFlash('success', 'Le livre a été ajouté avec succès.');

            return $this->redirectToRoute('admin_book_index');
        }

        return $this->render('admin_book/new.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->render('admin_book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_book_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Book $book, EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher): Response
    {
        // Sauvegarder les anciennes valeurs pour détecter les changements
        $oldPrice = $book->getPrice();
        $oldStock = $book->getStock();
        
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('books_directory'),
                    $newFilename
                );
                $book->setImage($newFilename);
            }

            $book->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            // Détecter les changements et déclencher l'événement
            $changes = [];
            
            if ($oldPrice !== $book->getPrice()) {
                $changes['price'] = [
                    'old' => $oldPrice,
                    'new' => $book->getPrice()
                ];
            }
            
            if ($oldStock !== $book->getStock()) {
                $changes['stock'] = [
                    'old' => $oldStock,
                    'new' => $book->getStock()
                ];
            }
            
            // Déclencher l'événement si des changements ont été détectés
            if (!empty($changes)) {
                $event = new BookUpdateEvent($book, $changes);
                $eventDispatcher->dispatch($event, BookUpdateEvent::NAME);
            }

            $this->addFlash('success', 'Le livre a été modifié avec succès.');

            return $this->redirectToRoute('admin_book_index');
        }

        return $this->render('admin_book/edit.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
            $entityManager->remove($book);
            $entityManager->flush();

            $this->addFlash('success', 'Le livre a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_book_index');
    }
}
