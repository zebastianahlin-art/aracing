<?php

declare(strict_types=1);

namespace App\Modules\Review\Services;

use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Review\Repositories\ProductReviewRepository;
use InvalidArgumentException;

final class ProductReviewService
{
    private const STATUSES = ['pending', 'approved', 'rejected', 'hidden'];

    public function __construct(
        private readonly ProductReviewRepository $reviews,
        private readonly ProductRepository $products
    ) {
    }

    /** @return array<int,string> */
    public function statuses(): array
    {
        return self::STATUSES;
    }

    /** @return array<string,string> */
    public function statusLabels(): array
    {
        return [
            'pending' => 'Väntar granskning',
            'approved' => 'Godkänd',
            'rejected' => 'Avslagen',
            'hidden' => 'Dold',
        ];
    }

    /** @return array<string,mixed> */
    public function publicSummaryForProduct(int $productId): array
    {
        return $this->reviews->summaryForProduct($productId);
    }

    /** @return array<int,array<string,mixed>> */
    public function publicReviewsForProduct(int $productId, int $limit = 30): array
    {
        return $this->reviews->listApprovedForProduct($productId, $limit);
    }

    public function createFromCustomer(int $userId, int $productId, array $input, array $customer): int
    {
        $product = $this->products->findById($productId);
        if ($product === null) {
            throw new InvalidArgumentException('Produkten hittades inte.');
        }

        if ($this->reviews->findByUserAndProduct($userId, $productId) !== null) {
            throw new InvalidArgumentException('Du har redan lämnat en recension för denna produkt.');
        }

        $rating = (int) ($input['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Betyg måste vara mellan 1 och 5.');
        }

        $reviewText = trim((string) ($input['review_text'] ?? ''));
        if ($reviewText === '') {
            throw new InvalidArgumentException('Skriv en kort recensionstext.');
        }

        $title = trim((string) ($input['title'] ?? ''));
        if (mb_strlen($title) > 190) {
            throw new InvalidArgumentException('Rubriken får vara max 190 tecken.');
        }

        if (mb_strlen($reviewText) > 5000) {
            throw new InvalidArgumentException('Recensionstexten är för lång.');
        }

        $verifiedOrder = $this->reviews->findVerifiedOrderForUserAndProduct($userId, $productId);
        $reviewId = $this->reviews->create([
            'product_id' => $productId,
            'user_id' => $userId,
            'order_id' => $verifiedOrder !== null ? (int) $verifiedOrder['id'] : null,
            'rating' => $rating,
            'title' => $title !== '' ? $title : null,
            'review_text' => $reviewText,
            'status' => 'pending',
            'is_verified_purchase' => $verifiedOrder !== null ? 1 : 0,
            'reviewer_name' => $this->reviewerName($customer),
            'published_at' => null,
        ]);

        $this->refreshProductSummary($productId);

        return $reviewId;
    }

    /** @return array<int,array<string,mixed>> */
    public function listAdmin(array $filters): array
    {
        return $this->reviews->listAdmin($filters);
    }

    /** @return array<string,mixed>|null */
    public function getAdminDetail(int $reviewId): ?array
    {
        return $this->reviews->findAdminById($reviewId);
    }

    public function moderate(int $reviewId, string $targetStatus): void
    {
        if (!in_array($targetStatus, self::STATUSES, true)) {
            throw new InvalidArgumentException('Ogiltig status för recension.');
        }

        $review = $this->reviews->findById($reviewId);
        if ($review === null) {
            throw new InvalidArgumentException('Recensionen hittades inte.');
        }

        if ((string) $review['status'] === $targetStatus) {
            return;
        }

        $publishedAt = $targetStatus === 'approved' ? date('Y-m-d H:i:s') : null;
        $this->reviews->updateModeration($reviewId, $targetStatus, $publishedAt);
        $this->refreshProductSummary((int) $review['product_id']);
    }

    public function refreshProductSummary(int $productId): void
    {
        $summary = $this->reviews->summaryForProduct($productId);
        $this->reviews->updateProductSummary($productId, (int) $summary['review_count'], (float) $summary['average_rating']);
    }

    /** @param array<string,mixed> $customer */
    private function reviewerName(array $customer): string
    {
        $name = trim((string) (($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')));
        if ($name !== '') {
            return mb_substr($name, 0, 190);
        }

        $email = trim((string) ($customer['email'] ?? 'Kund'));
        $fallback = explode('@', $email)[0] ?? $email;

        return mb_substr($fallback !== '' ? $fallback : 'Kund', 0, 190);
    }
}
