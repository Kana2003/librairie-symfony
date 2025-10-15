<?php

namespace App\Event;

use App\Entity\Book;
use Symfony\Contracts\EventDispatcher\Event;

class BookUpdateEvent extends Event
{
    public const NAME = 'book.updated';

    public function __construct(
        private Book $book,
        private array $changes
    ) {}

    public function getBook(): Book
    {
        return $this->book;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function hasPriceChanged(): bool
    {
        return isset($this->changes['price']);
    }

    public function hasStockChanged(): bool
    {
        return isset($this->changes['stock']);
    }

    public function getOldPrice(): ?float
    {
        return $this->changes['price']['old'] ?? null;
    }

    public function getNewPrice(): ?float
    {
        return $this->changes['price']['new'] ?? null;
    }

    public function getOldStock(): ?int
    {
        return $this->changes['stock']['old'] ?? null;
    }

    public function getNewStock(): ?int
    {
        return $this->changes['stock']['new'] ?? null;
    }
}
