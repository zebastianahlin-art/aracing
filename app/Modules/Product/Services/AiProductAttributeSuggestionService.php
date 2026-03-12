<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Core\Config\Config;
use App\Modules\Import\Repositories\AiProductImportDraftRepository;
use App\Modules\Product\Repositories\AiProductEnrichmentSuggestionRepository;
use InvalidArgumentException;

final class AiProductAttributeSuggestionService
{
    private const SUGGESTION_TYPE = 'attribute_summary';

    public function __construct(
        private readonly ProductService $products,
        private readonly AiProductEnrichmentSuggestionRepository $suggestions,
        private readonly AiProductImportDraftRepository $importDrafts,
        private readonly Config $config,
    ) {
    }

    /** @return array<int,array<string,mixed>> */
    public function listForProduct(int $productId): array
    {
        return $this->suggestions->listForProductByType($productId, self::SUGGESTION_TYPE, 20);
    }

    public function createSuggestionForProduct(int $productId, ?int $createdByUserId = null): int
    {
        $product = $this->products->get($productId);
        if ($product === null) {
            throw new InvalidArgumentException('Produkten hittades inte.');
        }

        $snapshot = $this->buildInputSnapshot($product);
        $generated = $this->generateSuggestionPayload($snapshot);

        $normalizedAttributes = $this->normalizeSuggestedAttributes($generated['suggested_attributes'] ?? null);
        if ($normalizedAttributes === []) {
            throw new InvalidArgumentException('AI-attributförslag kunde inte skapas: för lite verifierbart underlag.');
        }

        return $this->suggestions->create([
            'product_id' => $productId,
            'suggestion_type' => self::SUGGESTION_TYPE,
            'source_context' => (string) ($snapshot['source_context'] ?? 'manual'),
            'input_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'suggested_attributes' => json_encode($normalizedAttributes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'ai_summary' => $this->toNullableString($generated['ai_summary'] ?? null),
            'status' => 'pending',
            'created_by_user_id' => $createdByUserId,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
        ]);
    }

    public function applySuggestion(int $suggestionId, ?int $reviewedByUserId = null): void
    {
        $suggestion = $this->suggestions->findById($suggestionId);
        if ($suggestion === null) {
            throw new InvalidArgumentException('Attributförslaget hittades inte.');
        }

        if ((string) ($suggestion['suggestion_type'] ?? '') !== self::SUGGESTION_TYPE) {
            throw new InvalidArgumentException('Fel suggestion_type för attributapplicering.');
        }

        if ((string) ($suggestion['status'] ?? '') !== 'pending') {
            throw new InvalidArgumentException('Endast pending-attributförslag kan appliceras.');
        }

        $productId = (int) ($suggestion['product_id'] ?? 0);
        $product = $this->products->get($productId);
        if ($product === null) {
            throw new InvalidArgumentException('Produkten för attributförslaget hittades inte.');
        }

        $attributes = $this->normalizeSuggestedAttributes((string) ($suggestion['suggested_attributes'] ?? ''));
        if ($attributes === []) {
            throw new InvalidArgumentException('Attributförslaget är tomt eller ogiltigt.');
        }

        $payload = $this->buildProductUpdatePayload($product);
        $payload['attributes'] = $this->attributesTextFromMap($attributes);

        $this->products->update($productId, $payload);

        if ($this->suggestions->markApplied($suggestionId, $reviewedByUserId) === false) {
            throw new InvalidArgumentException('Attributförslaget kunde inte markeras som applicerat.');
        }
    }

    public function rejectSuggestion(int $suggestionId, ?int $reviewedByUserId = null): void
    {
        $suggestion = $this->suggestions->findById($suggestionId);
        if ($suggestion === null) {
            throw new InvalidArgumentException('Attributförslaget hittades inte.');
        }

        if ((string) ($suggestion['suggestion_type'] ?? '') !== self::SUGGESTION_TYPE) {
            throw new InvalidArgumentException('Fel suggestion_type för attributavvisning.');
        }

        if ((string) ($suggestion['status'] ?? '') !== 'pending') {
            throw new InvalidArgumentException('Endast pending-attributförslag kan avvisas.');
        }

        if ($this->suggestions->markRejected($suggestionId, $reviewedByUserId) === false) {
            throw new InvalidArgumentException('Attributförslaget kunde inte avvisas.');
        }
    }

    /** @param array<string,mixed> $product
     *  @return array<string,mixed>
     */
    private function buildInputSnapshot(array $product): array
    {
        $sourceContext = 'manual';
        $sourceDraft = null;

        if ((string) ($product['source_type'] ?? '') === 'ai_url_import' && (int) ($product['source_reference_id'] ?? 0) > 0) {
            $sourceContext = 'ai_url_import';
            $sourceDraft = $this->importDrafts->findById((int) $product['source_reference_id']);
        }

        return [
            'product_id' => (int) ($product['id'] ?? 0),
            'title' => trim((string) ($product['name'] ?? '')),
            'brand' => trim((string) ($product['brand_name'] ?? '')),
            'sku' => trim((string) ($product['sku'] ?? '')),
            'description' => trim((string) ($product['description'] ?? '')),
            'existing_attributes' => $this->extractExistingAttributes($product),
            'source_type' => $product['source_type'] ?? null,
            'source_reference_id' => $product['source_reference_id'] ?? null,
            'source_url' => $product['source_url'] ?? null,
            'source_context' => $sourceContext,
            'source_import_draft' => $sourceDraft !== null ? [
                'import_title' => $sourceDraft['import_title'] ?? null,
                'import_brand' => $sourceDraft['import_brand'] ?? null,
                'import_sku' => $sourceDraft['import_sku'] ?? null,
                'import_short_description' => $sourceDraft['import_short_description'] ?? null,
                'import_description' => $sourceDraft['import_description'] ?? null,
                'import_attributes' => $sourceDraft['import_attributes'] ?? null,
                'import_raw_text' => $sourceDraft['import_raw_text'] ?? null,
            ] : null,
        ];
    }

    /** @param array<string,mixed> $snapshot
     *  @return array<string,mixed>
     */
    private function generateSuggestionPayload(array $snapshot): array
    {
        $heuristic = $this->heuristicSuggestion($snapshot);
        $apiKey = trim((string) $this->config->get('ai.product_enrichment.openai_api_key', ''));
        if ($apiKey === '') {
            return $heuristic;
        }

        $aiPayload = $this->callOpenAi($apiKey, $snapshot);
        if (!is_array($aiPayload)) {
            return $heuristic;
        }

        return [
            'suggested_attributes' => $aiPayload['suggested_attributes'] ?? $heuristic['suggested_attributes'],
            'ai_summary' => $aiPayload['ai_summary'] ?? $heuristic['ai_summary'],
        ];
    }

    /** @param array<string,mixed> $snapshot
     *  @return array<string,mixed>
     */
    private function heuristicSuggestion(array $snapshot): array
    {
        $attributes = [];

        foreach ((array) ($snapshot['existing_attributes'] ?? []) as $key => $value) {
            $normalized = $this->normalizeAttributeKey((string) $key);
            if ($normalized === '' || trim((string) $value) === '') {
                continue;
            }
            $attributes[$normalized] = trim((string) $value);
        }

        $textSources = [
            (string) ($snapshot['title'] ?? ''),
            (string) ($snapshot['description'] ?? ''),
            (string) (($snapshot['source_import_draft']['import_short_description'] ?? '') ?: ''),
            (string) (($snapshot['source_import_draft']['import_description'] ?? '') ?: ''),
            (string) (($snapshot['source_import_draft']['import_raw_text'] ?? '') ?: ''),
        ];

        foreach ($textSources as $text) {
            foreach ($this->extractAttributesFromText($text) as $key => $value) {
                if (!isset($attributes[$key])) {
                    $attributes[$key] = $value;
                }
            }
        }

        return [
            'suggested_attributes' => $attributes,
            'ai_summary' => 'Attributförslag skapat med verifierbart underlag. Granska och applicera manuellt.',
        ];
    }

    /** @param array<string,mixed> $snapshot
     *  @return array<string,mixed>|null
     */
    private function callOpenAi(string $apiKey, array $snapshot): ?array
    {
        $payload = [
            'model' => 'gpt-4o-mini',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du extraherar och normaliserar produktattribut för svensk e-handelsadmin. Returnera ENDAST JSON med fält: suggested_attributes (objekt med key=>value) och ai_summary. Använd bara uppgifter som stöds av snapshot. Utelämna osäkra attribut. Hitta inte på fitment eller tekniska fakta.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'task' => 'attribute_extraction_normalization_v1',
                        'snapshot' => $snapshot,
                        'rules' => [
                            'review_first' => true,
                            'no_autopublish' => true,
                            'no_fitment' => true,
                            'language' => 'sv',
                        ],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ],
            'temperature' => 0.1,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        if ($ch === false) {
            return null;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            return null;
        }

        $decoded = json_decode((string) $response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            return null;
        }

        $json = json_decode($content, true);

        return is_array($json) ? $json : null;
    }

    /** @param array<string,mixed> $product
     *  @return array<string,string>
     */
    private function buildProductUpdatePayload(array $product): array
    {
        $payload = [
            'brand_id' => (string) ($product['brand_id'] ?? ''),
            'category_id' => (string) ($product['category_id'] ?? ''),
            'name' => (string) ($product['name'] ?? ''),
            'slug' => (string) ($product['slug'] ?? ''),
            'sku' => (string) ($product['sku'] ?? ''),
            'description' => (string) ($product['description'] ?? ''),
            'seo_title' => (string) ($product['seo_title'] ?? ''),
            'seo_description' => (string) ($product['seo_description'] ?? ''),
            'canonical_url' => (string) ($product['canonical_url'] ?? ''),
            'meta_robots' => (string) ($product['meta_robots'] ?? ''),
            'sale_price' => (string) ($product['sale_price'] ?? ''),
            'currency_code' => (string) ($product['currency_code'] ?? 'SEK'),
            'stock_status' => (string) ($product['stock_status'] ?? 'out_of_stock'),
            'stock_quantity' => (string) ((int) ($product['stock_quantity'] ?? 0)),
            'search_boost' => (string) ((int) ($product['search_boost'] ?? 0)),
            'sort_priority' => (string) ((int) ($product['sort_priority'] ?? 0)),
            'attributes' => $this->attributesTextFromMap($this->extractExistingAttributes($product)),
        ];

        foreach (['is_active', 'is_search_hidden', 'is_featured', 'is_indexable', 'backorder_allowed'] as $flag) {
            if ((int) ($product[$flag] ?? 0) === 1) {
                $payload[$flag] = '1';
            }
        }

        return $payload;
    }

    /** @param array<string,mixed> $product
     * @return array<string,string>
     */
    private function extractExistingAttributes(array $product): array
    {
        $result = [];
        foreach ((array) ($product['attributes'] ?? []) as $attribute) {
            if (!is_array($attribute)) {
                continue;
            }

            $key = $this->normalizeAttributeKey((string) ($attribute['attribute_key'] ?? ''));
            $value = trim((string) ($attribute['attribute_value'] ?? ''));
            if ($key === '' || $value === '') {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /** @return array<string,string> */
    private function normalizeSuggestedAttributes(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $key => $rawValue) {
            if (is_array($rawValue)) {
                continue;
            }

            $normalizedKey = $this->normalizeAttributeKey((string) $key);
            $normalizedValue = $this->normalizeAttributeValue((string) $rawValue);
            if ($normalizedKey === '' || $normalizedValue === '') {
                continue;
            }

            $normalized[$normalizedKey] = $normalizedValue;
        }

        return $normalized;
    }

    /** @return array<string,string> */
    private function extractAttributesFromText(string $text): array
    {
        $rows = preg_split('/\r\n|\r|\n/', strip_tags($text)) ?: [];
        $attributes = [];

        foreach ($rows as $row) {
            $line = trim(preg_replace('/\s+/u', ' ', $row) ?: '');
            if ($line === '' || mb_strlen($line) > 120) {
                continue;
            }

            if (!str_contains($line, ':')) {
                continue;
            }

            [$rawKey, $rawValue] = array_map('trim', explode(':', $line, 2));
            $key = $this->normalizeAttributeKey($rawKey);
            $value = $this->normalizeAttributeValue($rawValue);

            if ($key === '' || $value === '' || mb_strlen($value) > 80) {
                continue;
            }

            $attributes[$key] = $value;
        }

        return $attributes;
    }

    private function normalizeAttributeKey(string $key): string
    {
        $candidate = trim(mb_strtolower($key));
        if ($candidate === '') {
            return '';
        }

        $map = [
            'material' => 'Material',
            'färg' => 'Färg',
            'farg' => 'Färg',
            'diameter' => 'Diameter',
            'storlek' => 'Storlek',
            'vikt' => 'Vikt',
            'anslutning' => 'Anslutningstyp',
            'anslutningstyp' => 'Anslutningstyp',
            'serie' => 'Serie',
            'tillverkare' => 'Tillverkare',
            'bredd' => 'Bredd',
            'höjd' => 'Höjd',
            'hojd' => 'Höjd',
            'längd' => 'Längd',
            'langd' => 'Längd',
            'gänga' => 'Gänga',
            'ganga' => 'Gänga',
        ];

        if (isset($map[$candidate])) {
            return $map[$candidate];
        }

        if (mb_strlen($candidate) < 2 || mb_strlen($candidate) > 40) {
            return '';
        }

        return mb_convert_case($candidate, MB_CASE_TITLE, 'UTF-8');
    }

    private function normalizeAttributeValue(string $value): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?: '');
        if ($normalized === '') {
            return '';
        }

        return mb_substr($normalized, 0, 120);
    }

    /** @param array<string,string> $attributes */
    private function attributesTextFromMap(array $attributes): string
    {
        $rows = [];
        foreach ($attributes as $key => $value) {
            $rows[] = trim($key) . '|' . trim($value);
        }

        return implode(PHP_EOL, array_filter($rows, static fn (string $row): bool => $row !== '|' && trim($row) !== ''));
    }

    private function toNullableString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }
}
