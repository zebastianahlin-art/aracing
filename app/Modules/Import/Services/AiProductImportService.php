<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Import\Repositories\AiProductImportDraftRepository;
use InvalidArgumentException;

final class AiProductImportService
{
    private const ALLOWED_STATUSES = ['pending', 'reviewed', 'imported', 'rejected', 'failed'];
    private const ALLOWED_SOURCE_TYPES = ['supplier_product_page', 'generic_product_page', 'unknown'];

    public function __construct(
        private readonly AiProductImportDraftRepository $drafts,
        private readonly ProductPageFetchService $fetcher,
        private readonly ProductPageExtractService $extractor,
        private readonly AiProductStructuringService $aiStructurer,
    ) {
    }

    /** @return array{draft_id:int,status:string,error:?string} */
    public function createDraftFromUrl(string $url, ?int $createdByUserId = null): array
    {
        $normalizedUrl = $this->validateUrl($url);
        $domain = (string) (parse_url($normalizedUrl, PHP_URL_HOST) ?? '');

        $fetch = $this->fetcher->fetch($normalizedUrl);
        if (($fetch['ok'] ?? false) !== true) {
            $draftId = $this->drafts->create([
                'source_url' => $normalizedUrl,
                'source_domain' => $domain !== '' ? $domain : null,
                'source_type' => 'unknown',
                'status' => 'failed',
                'import_title' => null,
                'import_brand' => null,
                'import_sku' => null,
                'import_short_description' => null,
                'import_description' => null,
                'import_price' => null,
                'import_currency' => null,
                'import_stock_text' => null,
                'import_image_urls' => null,
                'import_attributes' => null,
                'import_raw_text' => null,
                'ai_summary' => null,
                'ai_structured_payload' => null,
                'review_note' => (string) ($fetch['error'] ?? 'Kunde inte hämta URL.'),
                'created_by_user_id' => $createdByUserId,
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
            ]);

            return ['draft_id' => $draftId, 'status' => 'failed', 'error' => (string) ($fetch['error'] ?? 'Kunde inte hämta URL.')];
        }

        $extracted = $this->extractor->extract($normalizedUrl, (string) ($fetch['body'] ?? ''));
        $structured = $this->aiStructurer->structure($extracted);
        $payload = is_array($structured['payload'] ?? null) ? $structured['payload'] : [];

        $status = trim((string) ($extracted['raw_text'] ?? '')) === '' ? 'failed' : 'pending';
        $sourceType = $this->inferSourceType($domain);

        $draftId = $this->drafts->create([
            'source_url' => $normalizedUrl,
            'source_domain' => $domain !== '' ? $domain : null,
            'source_type' => $sourceType,
            'status' => $status,
            'import_title' => $this->normalizeText($payload['title'] ?? $extracted['title'] ?? null, 255),
            'import_brand' => $this->normalizeText($payload['brand'] ?? null, 190),
            'import_sku' => $this->normalizeText($payload['sku'] ?? null, 120),
            'import_short_description' => $this->normalizeText($payload['short_description'] ?? null, 1200),
            'import_description' => $this->normalizeText($payload['description'] ?? $extracted['visible_text'] ?? null, 50000),
            'import_price' => $this->normalizePrice($payload['price'] ?? null),
            'import_currency' => $this->normalizeText($payload['currency'] ?? null, 12),
            'import_stock_text' => $this->normalizeText($payload['stock_text'] ?? null, 190),
            'import_image_urls' => $this->encodeJson($payload['image_urls'] ?? $extracted['images'] ?? []),
            'import_attributes' => $this->encodeJson($payload['attributes'] ?? []),
            'import_raw_text' => $this->normalizeText($extracted['raw_text'] ?? null, 50000),
            'ai_summary' => $this->normalizeText($structured['summary'] ?? null, 50000),
            'ai_structured_payload' => $this->encodeJson([
                'notes' => $structured['notes'] ?? null,
                'used_ai' => $structured['used_ai'] ?? false,
                'payload' => $payload,
            ]),
            'review_note' => $status === 'failed' ? 'Sidan gav otillräckligt textunderlag för tolkning.' : null,
            'created_by_user_id' => $createdByUserId,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
        ]);

        return ['draft_id' => $draftId, 'status' => $status, 'error' => $status === 'failed' ? 'Otillräckligt underlag extraherades från sidan.' : null];
    }

    /** @return array{rows:array<int,array<string,mixed>>,filters:array<string,string>,status_options:array<int,string>} */
    public function listDrafts(array $filters = []): array
    {
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '' && !in_array($status, self::ALLOWED_STATUSES, true)) {
            $status = '';
        }

        return [
            'rows' => $this->drafts->list(['status' => $status]),
            'filters' => ['status' => $status],
            'status_options' => self::ALLOWED_STATUSES,
        ];
    }

    /** @return array<string,mixed>|null */
    public function getDraft(int $id): ?array
    {
        return $this->drafts->findById($id);
    }

    public function markReviewed(int $id, ?int $reviewedByUserId = null, ?string $note = null): void
    {
        $this->assertDraftExists($id);
        $this->drafts->updateStatus($id, 'reviewed', $reviewedByUserId, $this->normalizeText($note, 2000));
    }

    public function markRejected(int $id, ?int $reviewedByUserId = null, ?string $note = null): void
    {
        $this->assertDraftExists($id);
        $this->drafts->updateStatus($id, 'rejected', $reviewedByUserId, $this->normalizeText($note, 2000));
    }

    public function markImported(int $id, ?int $reviewedByUserId = null, ?string $note = null): void
    {
        $this->assertDraftExists($id);
        $this->drafts->updateStatus($id, 'imported', $reviewedByUserId, $this->normalizeText($note, 2000));
    }

    private function validateUrl(string $url): string
    {
        $normalized = trim($url);
        if ($normalized === '' || filter_var($normalized, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Ange en giltig URL inklusive http:// eller https://.');
        }

        $scheme = strtolower((string) parse_url($normalized, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Endast http/https stöds i URL-import v1.');
        }

        return $normalized;
    }

    private function inferSourceType(string $domain): string
    {
        $normalized = strtolower(trim($domain));
        if ($normalized === '') {
            return 'unknown';
        }

        if (str_contains($normalized, 'supplier') || str_contains($normalized, 'grossist') || str_contains($normalized, 'vendor')) {
            return 'supplier_product_page';
        }

        return in_array('generic_product_page', self::ALLOWED_SOURCE_TYPES, true) ? 'generic_product_page' : 'unknown';
    }

    private function assertDraftExists(int $id): void
    {
        if ($id <= 0 || $this->drafts->findById($id) === null) {
            throw new InvalidArgumentException('Importutkastet kunde inte hittas.');
        }
    }

    private function normalizeText(mixed $value, int $maxLength): ?string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        return mb_substr($normalized, 0, $maxLength);
    }

    private function normalizePrice(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '' || is_numeric($value) === false) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function encodeJson(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $encoded;
    }
}
