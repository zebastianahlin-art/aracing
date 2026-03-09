<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

final class ProductImageStorageService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    private string $targetDirectory;

    public function __construct(?string $targetDirectory = null)
    {
        $base = dirname(__DIR__, 4);
        $this->targetDirectory = $targetDirectory ?? $base . '/public/uploads/product-images';
    }

    /** @param array<string,mixed> $file */
    public function storeUploadedFile(array $file, int $productId): string
    {
        $tmpFile = (string) ($file['tmp_name'] ?? '');
        if ($tmpFile === '' || !is_uploaded_file($tmpFile)) {
            throw new \RuntimeException('Ogiltig uppladdning.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) $finfo->file($tmpFile);
        if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new \RuntimeException('Endast JPG, PNG, WEBP och GIF stöds.');
        }

        $extension = self::ALLOWED_MIME_TYPES[$mimeType];
        $filename = sprintf('p%d_%s.%s', $productId, bin2hex(random_bytes(8)), $extension);

        if (!is_dir($this->targetDirectory) && !mkdir($this->targetDirectory, 0775, true) && !is_dir($this->targetDirectory)) {
            throw new \RuntimeException('Kunde inte skapa mediakatalog.');
        }

        $destination = $this->targetDirectory . '/' . $filename;
        if (!move_uploaded_file($tmpFile, $destination)) {
            throw new \RuntimeException('Kunde inte spara uppladdad bild.');
        }

        return '/uploads/product-images/' . $filename;
    }

    public function deleteByUrl(string $imageUrl): void
    {
        $normalized = trim($imageUrl);
        if (!str_starts_with($normalized, '/uploads/product-images/')) {
            return;
        }

        $filePath = dirname(__DIR__, 4) . '/public' . $normalized;
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
}
