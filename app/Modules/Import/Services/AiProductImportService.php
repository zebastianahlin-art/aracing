<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Import\Repositories\AiProductImportDraftRepository;
use App\Modules\Import\Services\SupplierParsers\SupplierProductParserResolver;
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
        private readonly SupplierProductParserResolver $parserResolver,
        private readonly AiImportQualityService $qualityService,
    ) {
    }

    /** @return array{draft_id:int,status:string,error:?string} */
    public function createDraftFromUrl(string $url, ?int $createdByUserId = null): array
    {
        $normalizedUrl = $this->validateUrl($url);
        $domain = (string) (parse_url($normalizedUrl, PHP_URL_HOST) ?? '');
        $resolvedParser = $this->parserResolver->resolve($domain);

        $fetch = $this->fetcher->fetch($normalizedUrl);
        if (($fetch['ok'] ?? false) !== true) {
            $draftId = $this->drafts->create([
                'source_url' => $normalizedUrl,
                'source_domain' => $domain !== '' ? $domain : null,
                'source_type' => 'unknown',
                'status' => 'failed',
                'parser_key' => $resolvedParser?->getParserKey(),
                'parser_version' => $resolvedParser?->getParserVersion(),
                'extraction_strategy' => $resolvedParser !== null ? 'supplier_parser_failed_fetch' : 'generic_ai_failed_fetch',
                'quality_label' => 'low',
                'confidence_summary' => 'Låg kvalitet: URL kunde inte hämtas och utkastet saknar extraherat underlag.',
                'missing_fields' => $this->encodeJson(['title', 'brand', 'sku', 'description', 'image_urls']),
                'quality_flags' => $this->encodeJson(['missing_title', 'missing_brand', 'missing_sku', 'missing_description', 'missing_images', 'weak_raw_text']),
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

        $parserInfo = ['parser_key' => null, 'parser_version' => null, 'strategy' => 'generic_ai'];
        $payload = [];
        $summary = null;
        $notes = null;
        $usedAi = false;
        $reviewNote = null;
        $parserMetadata = [];

        if ($resolvedParser !== null) {
            $parsed = $resolvedParser->parse($normalizedUrl, (string) ($fetch['body'] ?? ''));
            $parserInfo = [
                'parser_key' => $resolvedParser->getParserKey(),
                'parser_version' => $resolvedParser->getParserVersion(),
                'strategy' => 'supplier_parser',
            ];

            $parserFields = is_array($parsed['fields'] ?? null) ? $parsed['fields'] : [];
            $parserMetadata = is_array($parsed['metadata'] ?? null) ? $parsed['metadata'] : [];

            if (($parsed['ok'] ?? false) === true && $this->hasMeaningfulParserData($parserFields)) {
                $payload = $this->mergePreferPrimary($parserFields, [
                    'title' => $extracted['title'] ?? null,
                    'description' => $extracted['visible_text'] ?? null,
                    'image_urls' => $extracted['images'] ?? [],
                    'attributes' => [],
                ]);

                if ($this->shouldEnrichWithAi($payload)) {
                    $structured = $this->aiStructurer->structure($extracted);
                    $aiPayload = is_array($structured['payload'] ?? null) ? $structured['payload'] : [];
                    $payload = $this->mergePreferPrimary($payload, $aiPayload);
                    $summary = $this->normalizeText($structured['summary'] ?? null, 50000);
                    $notes = $structured['notes'] ?? null;
                    $usedAi = (bool) ($structured['used_ai'] ?? false);
                    $parserInfo['strategy'] = 'supplier_parser_plus_ai';
                }
            } else {
                $structured = $this->aiStructurer->structure($extracted);
                $payload = is_array($structured['payload'] ?? null) ? $structured['payload'] : [];
                $summary = $this->normalizeText($structured['summary'] ?? null, 50000);
                $notes = $structured['notes'] ?? null;
                $usedAi = (bool) ($structured['used_ai'] ?? false);
                $reviewNote = 'Supplier-parsern gav otillräckligt underlag; fallback till generisk AI-pipeline användes.';
                $parserInfo['strategy'] = 'supplier_parser_fallback_generic_ai';
                $parserMetadata['parser_error'] = $parsed['error'] ?? 'okänd parseravvikelse';
            }
        } else {
            $structured = $this->aiStructurer->structure($extracted);
            $payload = is_array($structured['payload'] ?? null) ? $structured['payload'] : [];
            $summary = $this->normalizeText($structured['summary'] ?? null, 50000);
            $notes = $structured['notes'] ?? null;
            $usedAi = (bool) ($structured['used_ai'] ?? false);
        }

        $rawText = $this->normalizeText($extracted['raw_text'] ?? null, 50000);
        $status = $rawText === null ? 'failed' : 'pending';
        $sourceType = $resolvedParser !== null ? 'supplier_product_page' : $this->inferSourceType($domain);

        $draftData = [
            'source_url' => $normalizedUrl,
            'source_domain' => $domain !== '' ? $domain : null,
            'source_type' => $sourceType,
            'status' => $status,
            'parser_key' => $parserInfo['parser_key'],
            'parser_version' => $parserInfo['parser_version'],
            'extraction_strategy' => $parserInfo['strategy'],
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
            'import_raw_text' => $rawText,
            'ai_summary' => $summary,
            'ai_structured_payload' => $this->encodeJson([
                'notes' => $notes,
                'used_ai' => $usedAi,
                'payload' => $payload,
                'parser' => [
                    'key' => $parserInfo['parser_key'],
                    'version' => $parserInfo['parser_version'],
                    'strategy' => $parserInfo['strategy'],
                    'metadata' => $parserMetadata,
                ],
            ]),
            'review_note' => $status === 'failed' ? 'Sidan gav otillräckligt textunderlag för tolkning.' : $reviewNote,
            'created_by_user_id' => $createdByUserId,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
        ];

        $quality = $this->qualityService->analyzeDraft($draftData);
        $draftData['quality_label'] = $quality['quality_label'];
        $draftData['confidence_summary'] = $quality['confidence_summary'];
        $draftData['missing_fields'] = $this->encodeJson($quality['missing_fields']);
        $draftData['quality_flags'] = $this->encodeJson($quality['quality_flags']);

        $draftId = $this->drafts->create($draftData);

        return ['draft_id' => $draftId, 'status' => $status, 'error' => $status === 'failed' ? 'Otillräckligt underlag extraherades från sidan.' : null];
    }

    /** @return array{rows:array<int,array<string,mixed>>,filters:array<string,string>,status_options:array<int,string>,quality_options:array<int,string>} */
    public function listDrafts(array $filters = []): array
    {
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '' && !in_array($status, self::ALLOWED_STATUSES, true)) {
            $status = '';
        }

        $qualityLabel = trim((string) ($filters['quality_label'] ?? ''));
        if ($qualityLabel !== '' && !in_array($qualityLabel, ['high', 'medium', 'low'], true)) {
            $qualityLabel = '';
        }

        return [
            'rows' => $this->drafts->list(['status' => $status, 'quality_label' => $qualityLabel]),
            'filters' => ['status' => $status, 'quality_label' => $qualityLabel],
            'status_options' => self::ALLOWED_STATUSES,
            'quality_options' => ['high', 'medium', 'low'],
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

    public function refreshDraftQuality(int $id): void
    {
        $draft = $this->drafts->findById($id);
        if ($draft === null) {
            throw new InvalidArgumentException('Importutkastet kunde inte hittas.');
        }

        $quality = $this->qualityService->analyzeDraft($draft);
        $this->drafts->updateQualityMetadata($id, [
            'quality_label' => $quality['quality_label'],
            'confidence_summary' => $quality['confidence_summary'],
            'missing_fields' => $this->encodeJson($quality['missing_fields']),
            'quality_flags' => $this->encodeJson($quality['quality_flags']),
        ]);
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

    /** @param array<string,mixed> $primary @param array<string,mixed> $fallback @return array<string,mixed> */
    private function mergePreferPrimary(array $primary, array $fallback): array
    {
        $merged = $fallback;

        foreach ($primary as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            if (is_array($value) && $value === []) {
                continue;
            }

            if (is_array($value) && is_array($merged[$key] ?? null)) {
                $merged[$key] = array_merge($merged[$key], $value);
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    /** @param array<string,mixed> $fields */
    private function hasMeaningfulParserData(array $fields): bool
    {
        return trim((string) ($fields['title'] ?? '')) !== ''
            || trim((string) ($fields['sku'] ?? '')) !== ''
            || trim((string) ($fields['description'] ?? '')) !== '';
    }

    /** @param array<string,mixed> $fields */
    private function shouldEnrichWithAi(array $fields): bool
    {
        return trim((string) ($fields['short_description'] ?? '')) === ''
            || trim((string) ($fields['description'] ?? '')) === '';
    }
}
