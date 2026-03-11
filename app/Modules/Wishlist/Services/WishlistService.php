<?php

declare(strict_types=1);

namespace App\Modules\Wishlist\Services;

use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Wishlist\Repositories\WishlistRepository;
use InvalidArgumentException;

final class WishlistService
{
    public function __construct(
        private readonly WishlistRepository $wishlist,
        private readonly ProductRepository $products
    ) {
    }

    public function addProduct(int $userId, int $productId): void
    {
        $this->assertValidIdentifiers($userId, $productId);

        if ($this->products->findById($productId) === null) {
            throw new InvalidArgumentException('Produkten kunde inte hittas.');
        }

        $this->wishlist->addProduct($userId, $productId);
    }

    public function removeProduct(int $userId, int $productId): void
    {
        $this->assertValidIdentifiers($userId, $productId);
        $this->wishlist->removeProduct($userId, $productId);
    }

    public function isSaved(int $userId, int $productId): bool
    {
        $this->assertValidIdentifiers($userId, $productId);

        return $this->wishlist->hasProduct($userId, $productId);
    }

    /** @return array<int, array<string, mixed>> */
    public function listPublicSavedProducts(int $userId): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Ogiltig användare.');
        }

        return $this->wishlist->publicProductsForUser($userId);
    }

    private function assertValidIdentifiers(int $userId, int $productId): void
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Ogiltig användare.');
        }

        if ($productId <= 0) {
            throw new InvalidArgumentException('Ogiltig produkt.');
        }
    }
}
