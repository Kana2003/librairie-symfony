<?php

namespace App\Command;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-books',
    description: 'Charge les livres informatiques dans la base de données',
)]
class LoadBooksCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $books = [
            [
                'title' => 'Algorithmique',
                'description' => 'Un guide complet pour comprendre et maîtriser les algorithmes fondamentaux en informatique.',
                'author' => 'Jean Dupont',
                'image' => 'Algorithmique.jpg',
                'price' => 45.99,
                'stock' => 15
            ],
            [
                'title' => 'Bootstrap 5',
                'description' => 'Apprenez à créer des interfaces web modernes et responsives avec Bootstrap 5.',
                'author' => 'Marie Martin',
                'image' => 'Bootstrap-5.jpg',
                'price' => 39.99,
                'stock' => 8
            ],
            [
                'title' => 'C#',
                'description' => 'Maîtrisez le langage C# et le développement d\'applications .NET.',
                'author' => 'Pierre Durand',
                'image' => 'CSharp.jpg',
                'price' => 52.99,
                'stock' => 12
            ],
            [
                'title' => 'C# et Blazor',
                'description' => 'Développez des applications web modernes avec C# et Blazor.',
                'author' => 'Sophie Bernard',
                'image' => 'CSharp-Et-Blazor.jpg',
                'price' => 48.99,
                'stock' => 6
            ],
            [
                'title' => 'Conception du modèle technologique',
                'description' => 'Guide pratique pour la conception et l\'architecture des systèmes informatiques.',
                'author' => 'Lucas Moreau',
                'image' => 'conception-du-modele-technologique.jpg',
                'price' => 65.99,
                'stock' => 4
            ],
            [
                'title' => 'Développement Informatique',
                'description' => 'Les fondamentaux du développement informatique moderne.',
                'author' => 'Emma Rousseau',
                'image' => 'Developpement-Informatique.jpg',
                'price' => 42.99,
                'stock' => 20
            ],
            [
                'title' => 'Hacking',
                'description' => 'Sécurité informatique et techniques de hacking éthique.',
                'author' => 'Thomas Leroy',
                'image' => 'Hacking.jpg',
                'price' => 55.99,
                'stock' => 3
            ],
            [
                'title' => 'HTML5',
                'description' => 'Maîtrisez HTML5 pour créer des sites web modernes.',
                'author' => 'Julie Dubois',
                'image' => 'HTML5.jpg',
                'price' => 29.99,
                'stock' => 25
            ],
            [
                'title' => 'Informatique Bureautique et Systèmes d\'Information',
                'description' => 'Comprendre les systèmes d\'information et la bureautique moderne.',
                'author' => 'Nicolas Petit',
                'image' => 'Informatique-Bureautique-Systemes-D-Information.jpg',
                'price' => 38.99,
                'stock' => 18
            ],
            [
                'title' => 'Introduction à l\'Informatique Quantique',
                'description' => 'Découvrez les bases de l\'informatique quantique et ses applications.',
                'author' => 'Camille Roux',
                'image' => 'Introduction-Informatique-Quantique.jpg',
                'price' => 72.99,
                'stock' => 2
            ],
            [
                'title' => 'Langage R',
                'description' => 'Programmation statistique et analyse de données avec R.',
                'author' => 'Antoine Simon',
                'image' => 'Langage-R.jpg',
                'price' => 44.99,
                'stock' => 10
            ],
            [
                'title' => 'Machine Learning',
                'description' => 'Apprentissage automatique et intelligence artificielle.',
                'author' => 'Claire Mercier',
                'image' => 'Machine-Learning.jpg',
                'price' => 68.99,
                'stock' => 7
            ],
            [
                'title' => 'Mobile avec C#',
                'description' => 'Développement d\'applications mobiles avec C# et Xamarin.',
                'author' => 'David Laurent',
                'image' => 'Mobile-Avec-CSharp.jpg',
                'price' => 49.99,
                'stock' => 9
            ],
            [
                'title' => 'PHP',
                'description' => 'Programmation web dynamique avec PHP.',
                'author' => 'Sarah Girard',
                'image' => 'PHP.jpg',
                'price' => 41.99,
                'stock' => 14
            ],
            [
                'title' => 'PHP 7 et MariaDB',
                'description' => 'Développement web avec PHP 7 et base de données MariaDB.',
                'author' => 'Marc Lefevre',
                'image' => 'PHP-7-Et-MariaDB.jpg',
                'price' => 46.99,
                'stock' => 11
            ],
            [
                'title' => 'Python 3',
                'description' => 'Programmation moderne avec Python 3.',
                'author' => 'Laura Fontaine',
                'image' => 'Python-3.jpg',
                'price' => 43.99,
                'stock' => 16
            ],
            [
                'title' => 'Réseaux Informatiques',
                'description' => 'Comprendre et configurer les réseaux informatiques.',
                'author' => 'François Denis',
                'image' => 'Reseaux-Informatiques.jpg',
                'price' => 51.99,
                'stock' => 13
            ],
            [
                'title' => 'Sécurité Réseaux Cisco',
                'description' => 'Sécurisation des réseaux avec les technologies Cisco.',
                'author' => 'Vincent Rousseau',
                'image' => 'Securite-Reseaux-Cisco.jpg',
                'price' => 74.99,
                'stock' => 5
            ],
            [
                'title' => 'TCP/IP',
                'description' => 'Protocoles TCP/IP et communication réseau.',
                'author' => 'Isabelle Moreau',
                'image' => 'TCP-IP.jpg',
                'price' => 47.99,
                'stock' => 12
            ],
            [
                'title' => 'Technologie minimale de conception',
                'description' => 'Approches minimalistes pour la conception technologique.',
                'author' => 'Paul Martin',
                'image' => 'Technologie-minimale-de-conception.jpg',
                'price' => 58.99,
                'stock' => 6
            ],
            [
                'title' => 'Windows 10 Pour Les Nuls',
                'description' => 'Guide complet pour maîtriser Windows 10.',
                'author' => 'Michel Durand',
                'image' => 'Windows-10-Pour-Les-Nuls.jpg',
                'price' => 32.99,
                'stock' => 22
            ],
            [
                'title' => 'WordPress 3',
                'description' => 'Création de sites web avec WordPress 3.',
                'author' => 'Catherine Leroy',
                'image' => 'WordPress-3.jpeg',
                'price' => 35.99,
                'stock' => 19
            ]
        ];

        $io->title('Chargement des livres informatiques');
        $io->progressStart(count($books));

        foreach ($books as $bookData) {
            $book = new Book();
            $book->setTitle($bookData['title']);
            $book->setDescription($bookData['description']);
            $book->setAuthor($bookData['author']);
            $book->setImage($bookData['image']);
            $book->setPrice($bookData['price']);
            $book->setStock($bookData['stock']);
            $book->setIsActive(true);

            $this->entityManager->persist($book);
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('%d livres ont été chargés avec succès !', count($books)));

        return Command::SUCCESS;
    }
}
