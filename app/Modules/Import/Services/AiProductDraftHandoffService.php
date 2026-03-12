<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Import\Repositories\AiProductImportDraftRepository;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Services\ProductService;
use InvalidArgumentException;

final class AiProductDraftHandoffService
{
    public function __construct(
        private readonly AiProductImportDraftRepository $drafts,
        private readonly ProductService $products,
        private readonly ProductRepository $productRepository,
    ) {
    }

    /** @return array{product_id:int} */
    public function handoffToProductDraft(int $draftId, ?int $userId = null): array
    {
        $draft = $this->drafts->findById($draftId);
        if ($draft === null) {
            throw new InvalidArgumentException('Importutkastet kunde inte hittas.');
        }

        $this->assertHandoffAllowed($draft);

        $input = $this->mapDraftToProductInput($draft);
        $productId = $this->products->create($input);

        $this->productRepository->setSourceReference(
            $productId,
            'ai_url_import',
            (int) $draft['id'],
            (string) ($draft['source_url'] ?? '')
        );

        $marked = $this->drafts->markHandedOff($draftId, 'product', $productId, $userId);
        if ($marked === false) {
            throw new InvalidArgumentException('Utkastet har redan handoffats till katalogflödet.');
        }

        $this->drafts->updateStatus(
            $draftId,
            'imported',
            $userId,
            'Handoff till produktutkast #' . $productId . ' genomförd. Fortsätt i artikelvårdskön.'
        );

        return ['product_id' => $productId];
    }

    /** @param array<string,mixed> $draft */
    private function assertHandoffAllowed(array $draft): void
    {
        if ((string) ($draft['status'] ?? '') !== 'reviewed') {
            throw new InvalidArgumentException('Handoff kräver status reviewed.');
        }

        if (($draft['handed_off_at'] ?? null) !== null) {
            throw new InvalidArgumentException('Utkastet har redan handoffats till katalogflödet.');
        }

        $title = trim((string) ($draft['import_title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Handoff kräver import_title (titel) för att skapa produktutkast.');
        }
    }

    /** @param array<string,mixed> $draft
     *  @return array<string,string>
     */
    private function mapDraftToProductInput(array $draft): array
    {
        $title = trim((string) ($draft['import_title'] ?? ''));
        $description = trim((string) ($draft['import_description'] ?? ''));
        $shortDescription = trim((string) ($draft['import_short_description'] ?? ''));

        $attributes = $this->mapAttributes($draft['import_attributes'] ?? null);
        $images = $this->mapImages($draft['import_image_urls'] ?? null, $title);

        return [
            'name' => $title,
            'slug' => $this->buildDraftSlug($title, (int) $draft['id']),
            'sku' => trim((string) ($draft['import_sku'] ?? '')),
            'description' => $description !== '' ? $description : $shortDescription,
            'sale_price' => trim((string) ($draft['import_price'] ?? '')),
            'currency_code' => trim((string) ($draft['import_currency'] ?? '')),
            'stock_status' => $this->deriveStockStatus((string) ($draft['import_stock_text'] ?? '')),
            'stock_quantity' => '',
            'backorder_allowed' => 0,
            'is_active' => '0',
            'is_search_hidden' => '0',
            'is_featured' => '0',
            'search_boost' => '0',
            'sort_priority' => '0',
            'seo_title' => '',
            'seo_description' => '',
            'canonical_url' => '',
            'meta_robots' => 'index,follow',
            'attributes' => $attributes,
            'images' => $images,
        ];
    }

    private function buildDraftSlug(string $title, int $draftId): string
    {
        $base = mb_strtolower(trim(preg_replace('/[^a-zA-Z0-9\s\-]+/u', '-', $title) ?? ''));
        $base = trim(preg_replace('/\s+/u', '-', $base) ?? '', '-');
        $base = trim(preg_replace('/\-+/u', '-', $base) ?? '', '-');
        if ($base === '') {
            $base = 'ai-import';
        }

        return mb_substr($base, 0, 200) . '-draft-' . $draftId;
    }

    private function deriveStockStatus(string $stockText): string
    {
        $value = mb_strtolower(trim($stockText));
        if ($value === '') {
            return 'out_of_stock';
        }

        if (str_contains($value, 'i lager') || str_contains($value, 'in stock')) {
            return 'in_stock';
        }

        if (str_contains($value, 'backorder') || str_contains($value, 'beställ')) {
            return 'backorder';
        }

        return 'out_of_stock';
    }

    private function mapAttributes(mixed $attributes): string
    {
        $decoded = json_decode((string) $attributes, true);
        if (!is_array($decoded)) {
            return '';
        }

        $lines = [];
        foreach ($decoded as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map(static fn (mixed $part): string => trim((string) $part), $value));
            }

            $attributeKey = trim(is_string($key) ? $key : 'attribute_' . (string) $key);
            $attributeValue = trim((string) $value);

            if ($attributeKey === '' || $attributeValue === '') {
                continue;
            }

            $lines[] = $attributeKey . '|' . $attributeValue;
        }

        return implode(PHP_EOL, $lines);
    }

    private function mapImages(mixed $images, string $title): string
    {
        $decoded = json_decode((string) $images, true);
        if (!is_array($decoded)) {
            return '';
        }

        $lines = [];
        foreach (array_values($decoded) as $index => $url) {
            $normalizedUrl = trim((string) $url);
            if ($normalizedUrl === '') {
                continue;
            }

            $lines[] = implode('|', [
                $normalizedUrl,
                $title,
                (string) $index,
                $index === 0 ? '1' : '0',
            ]);
        }

        return implode(PHP_EOL, $lines);
    }
}
