<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Modules\Product\Repositories\ProductImageRepository;
use App\Modules\Product\Repositories\ProductRepository;

final class ProductMediaService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductImageRepository $images,
        private readonly ProductImageStorageService $storage
    ) {
    }

    /** @param array<string,mixed> $files */
    public function uploadImages(int $productId, array $files, string $defaultAltText = ''): int
    {
        $this->assertProductExists($productId);

        $normalizedFiles = $this->normalizeUploadedFiles($files);
        if ($normalizedFiles === []) {
            throw new \RuntimeException('Välj minst en bild att ladda upp.');
        }

        $created = 0;
        foreach ($normalizedFiles as $file) {
            if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                continue;
            }

            $imageUrl = $this->storage->storeUploadedFile($file, $productId);
            $sortOrder = $this->images->nextSortOrder($productId);
            $isPrimary = $this->images->hasPrimary($productId) ? 0 : 1;
            $this->images->create($productId, $imageUrl, trim($defaultAltText), $sortOrder, $isPrimary);
            $created++;
        }

        if ($created === 0) {
            throw new \RuntimeException('Ingen bild kunde laddas upp.');
        }

        return $created;
    }

    public function updateImageMeta(int $productId, int $imageId, string $altText, int $sortOrder, bool $isPrimary): void
    {
        $this->assertProductExists($productId);
        $image = $this->images->findForProduct($productId, $imageId);
        if ($image === null) {
            throw new \RuntimeException('Bild hittades inte för produkten.');
        }

        $this->images->updateMeta($productId, $imageId, trim($altText), $sortOrder, $isPrimary);
    }

    public function setPrimaryImage(int $productId, int $imageId): void
    {
        $this->assertProductExists($productId);
        $image = $this->images->findForProduct($productId, $imageId);
        if ($image === null) {
            throw new \RuntimeException('Bild hittades inte för produkten.');
        }

        $this->images->markPrimary($productId, $imageId);
    }

    public function deleteImage(int $productId, int $imageId): void
    {
        $this->assertProductExists($productId);
        $image = $this->images->findForProduct($productId, $imageId);
        if ($image === null) {
            return;
        }

        $this->images->deleteForProduct($productId, $imageId);
        $this->storage->deleteByUrl((string) ($image['image_url'] ?? ''));

        $remaining = $this->images->byProductId($productId);
        if ($remaining !== [] && !array_filter($remaining, static fn (array $row): bool => (int) $row['is_primary'] === 1)) {
            $first = (int) $remaining[0]['id'];
            $this->images->markPrimary($productId, $first);
        }
    }

    private function assertProductExists(int $productId): void
    {
        if ($this->products->findById($productId) === null) {
            throw new \RuntimeException('Produkten hittades inte.');
        }
    }

    /** @param array<string,mixed> $files
     *  @return array<int,array<string,mixed>>
     */
    private function normalizeUploadedFiles(array $files): array
    {
        $input = $files['images'] ?? null;
        if (!is_array($input) || !isset($input['name']) || !is_array($input['name'])) {
            return [];
        }

        $normalized = [];
        foreach ($input['name'] as $index => $name) {
            $normalized[] = [
                'name' => (string) $name,
                'type' => (string) ($input['type'][$index] ?? ''),
                'tmp_name' => (string) ($input['tmp_name'][$index] ?? ''),
                'error' => (int) ($input['error'][$index] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($input['size'][$index] ?? 0),
            ];
        }

        return $normalized;
    }
}
