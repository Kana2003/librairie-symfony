<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(BookRepository $bookRepository, Request $request): Response
    {
        $search = $request->query->get('search');

        if ($search) {
            $books = $bookRepository->searchBooks($search);
        } else {
            $books = $bookRepository->findAvailableBooks();
        }

        return $this->render('home/index.html.twig', [
            'books' => $books,
            'search' => $search,
        ]);
    }
}
