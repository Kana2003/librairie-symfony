<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function save(Book $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Book $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAvailableBooks(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.isActive = :active')
            ->andWhere('b.stock > 0')
            ->setParameter('active', true)
            ->orderBy('b.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchBooks(string $search): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.isActive = :active')
            ->andWhere('b.title LIKE :search OR b.author LIKE :search OR b.description LIKE :search')
            ->setParameter('active', true)
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('b.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLowStockBooks(int $threshold = 5): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.isActive = :active')
            ->andWhere('b.stock <= :threshold')
            ->andWhere('b.stock > 0')
            ->setParameter('active', true)
            ->setParameter('threshold', $threshold)
            ->orderBy('b.stock', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOutOfStockBooks(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.isActive = :active')
            ->andWhere('b.stock = 0')
            ->setParameter('active', true)
            ->orderBy('b.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
