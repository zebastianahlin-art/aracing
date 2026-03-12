<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Core\Config\Config;
use App\Modules\Import\Repositories\AiProductImportDraftRepository;
use App\Modules\Product\Repositories\AiProductEnrichmentSuggestionRepository;
use InvalidArgumentException;

final class AiProductEnrichmentService
{
    private const ALLOWED_TYPES = ['content_cleanup', 'title_description', 'seo_assist', 'attribute_summary'];

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
        return $this->suggestions->listForProduct($productId);
    }

    public function createSuggestionForProduct(int $productId, string $suggestionType, ?int $createdByUserId = null): int
    {
        $type = trim($suggestionType);
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException('Ogiltig suggestion_type.');
        }

        $product = $this->products->get($productId);
        if ($product === null) {
            throw new InvalidArgumentException('Produkten hittades inte.');
        }

        $snapshot = $this->buildInputSnapshot($product);
        $generated = $this->generateSuggestionPayload($type, $snapshot);

        if ($this->isSuggestionEmpty($generated)) {
            throw new InvalidArgumentException('AI-förslag kunde inte skapas med tillräckligt underlag.');
        }

        return $this->suggestions->create([
            'product_id' => $productId,
            'suggestion_type' => $type,
            'source_context' => (string) ($snapshot['source_context'] ?? 'manual'),
            'input_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'suggested_title' => $this->toNullableString($generated['suggested_title'] ?? null, 255),
            'suggested_short_description' => $this->toNullableString($generated['suggested_short_description'] ?? null),
            'suggested_description' => $this->toNullableString($generated['suggested_description'] ?? null),
            'suggested_attributes' => $this->normalizeSuggestedAttributes($generated['suggested_attributes'] ?? null),
            'suggested_seo_title' => $this->toNullableString($generated['suggested_seo_title'] ?? null, 255),
            'suggested_meta_description' => $this->toNullableString($generated['suggested_meta_description'] ?? null),
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
            throw new InvalidArgumentException('Förslaget hittades inte.');
        }
        if ((string) $suggestion['status'] !== 'pending') {
            throw new InvalidArgumentException('Endast pending-förslag kan appliceras.');
        }

        $productId = (int) ($suggestion['product_id'] ?? 0);
        $product = $this->products->get($productId);
        if ($product === null) {
            throw new InvalidArgumentException('Produkten för förslaget hittades inte.');
        }

        $updatePayload = $this->buildProductUpdatePayload($product);
        $type = (string) ($suggestion['suggestion_type'] ?? '');

        if ((string) ($suggestion['suggested_title'] ?? '') !== '' && in_array($type, ['content_cleanup', 'title_description'], true)) {
            $updatePayload['name'] = (string) $suggestion['suggested_title'];
        }

        if ((string) ($suggestion['suggested_description'] ?? '') !== '' && in_array($type, ['content_cleanup', 'title_description'], true)) {
            $updatePayload['description'] = (string) $suggestion['suggested_description'];
        }

        if ((string) ($suggestion['suggested_seo_title'] ?? '') !== '' && in_array($type, ['seo_assist', 'title_description'], true)) {
            $updatePayload['seo_title'] = (string) $suggestion['suggested_seo_title'];
        }

        if ((string) ($suggestion['suggested_meta_description'] ?? '') !== '' && in_array($type, ['seo_assist', 'title_description'], true)) {
            $updatePayload['seo_description'] = (string) $suggestion['suggested_meta_description'];
        }

        if ((string) ($suggestion['suggested_attributes'] ?? '') !== '' && in_array($type, ['attribute_summary', 'content_cleanup'], true)) {
            $updatePayload['attributes'] = $this->attributesTextFromSuggestion((string) $suggestion['suggested_attributes']);
        }

        $this->products->update($productId, $updatePayload);

        if ($this->suggestions->markApplied($suggestionId, $reviewedByUserId) === false) {
            throw new InvalidArgumentException('Förslaget kunde inte markeras som applicerat.');
        }
    }

    public function rejectSuggestion(int $suggestionId, ?int $reviewedByUserId = null): void
    {
        $suggestion = $this->suggestions->findById($suggestionId);
        if ($suggestion === null) {
            throw new InvalidArgumentException('Förslaget hittades inte.');
        }

        if ((string) ($suggestion['status'] ?? '') !== 'pending') {
            throw new InvalidArgumentException('Endast pending-förslag kan avvisas.');
        }

        if ($this->suggestions->markRejected($suggestionId, $reviewedByUserId) === false) {
            throw new InvalidArgumentException('Förslaget kunde inte avvisas.');
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
            'name' => (string) ($product['name'] ?? ''),
            'sku' => (string) ($product['sku'] ?? ''),
            'brand_id' => $product['brand_id'] ?? null,
            'category_id' => $product['category_id'] ?? null,
            'description' => (string) ($product['description'] ?? ''),
            'seo_title' => (string) ($product['seo_title'] ?? ''),
            'seo_description' => (string) ($product['seo_description'] ?? ''),
            'attributes' => $product['attributes'] ?? [],
            'source_type' => $product['source_type'] ?? null,
            'source_reference_id' => $product['source_reference_id'] ?? null,
            'source_url' => $product['source_url'] ?? null,
            'source_context' => $sourceContext,
            'source_import_draft' => $sourceDraft,
        ];
    }

    /** @param array<string,mixed> $snapshot
     *  @return array<string,mixed>
     */
    private function generateSuggestionPayload(string $type, array $snapshot): array
    {
        $heuristic = $this->heuristicSuggestion($type, $snapshot);
        $apiKey = trim((string) $this->config->get('ai.product_enrichment.openai_api_key', ''));
        if ($apiKey === '') {
            return $heuristic;
        }

        $aiPayload = $this->callOpenAi($apiKey, $type, $snapshot);
        if ($aiPayload === null) {
            return $heuristic;
        }

        return [
            'suggested_title' => $aiPayload['suggested_title'] ?? $heuristic['suggested_title'],
            'suggested_short_description' => $aiPayload['suggested_short_description'] ?? $heuristic['suggested_short_description'],
            'suggested_description' => $aiPayload['suggested_description'] ?? $heuristic['suggested_description'],
            'suggested_attributes' => $aiPayload['suggested_attributes'] ?? $heuristic['suggested_attributes'],
            'suggested_seo_title' => $aiPayload['suggested_seo_title'] ?? $heuristic['suggested_seo_title'],
            'suggested_meta_description' => $aiPayload['suggested_meta_description'] ?? $heuristic['suggested_meta_description'],
            'ai_summary' => $aiPayload['ai_summary'] ?? $heuristic['ai_summary'],
        ];
    }

    /** @param array<string,mixed> $snapshot
     *  @return array<string,mixed>
     */
    private function heuristicSuggestion(string $type, array $snapshot): array
    {
        $name = trim((string) ($snapshot['name'] ?? ''));
        $description = trim((string) ($snapshot['description'] ?? ''));
        $short = mb_substr(preg_replace('/\s+/u', ' ', strip_tags($description)) ?: '', 0, 260);

        $title = $name;
        if ($type === 'content_cleanup') {
            $title = mb_substr(trim(preg_replace('/\s+/u', ' ', $name) ?: ''), 0, 255);
        }

        $seoTitle = mb_substr($title !== '' ? $title . ' | A-Racing' : 'A-Racing', 0, 255);
        $seoDescription = $short !== '' ? $short : 'Produktdata föreslagen för manuell granskning i A-Racing.';

        $attributes = [];
        if (is_array($snapshot['attributes'] ?? null)) {
            foreach ($snapshot['attributes'] as $attribute) {
                if (!is_array($attribute)) {
                    continue;
                }
                $key = trim((string) ($attribute['attribute_key'] ?? ''));
                $value = trim((string) ($attribute['attribute_value'] ?? ''));
                if ($key === '' || $value === '') {
                    continue;
                }
                $attributes[$key] = $value;
            }
        }

        if ($type === 'attribute_summary' && $attributes === []) {
            $attributes['sammanfattning'] = $short !== '' ? $short : 'Komplettera attribut manuellt efter granskning.';
        }

        return [
            'suggested_title' => in_array($type, ['content_cleanup', 'title_description'], true) ? $title : null,
            'suggested_short_description' => in_array($type, ['content_cleanup', 'title_description'], true) ? $short : null,
            'suggested_description' => in_array($type, ['content_cleanup', 'title_description'], true)
                ? ($description !== '' ? $description : $short)
                : null,
            'suggested_attributes' => in_array($type, ['attribute_summary', 'content_cleanup'], true) ? $attributes : null,
            'suggested_seo_title' => in_array($type, ['seo_assist', 'title_description'], true) ? $seoTitle : null,
            'suggested_meta_description' => in_array($type, ['seo_assist', 'title_description'], true) ? $seoDescription : null,
            'ai_summary' => 'Förslag skapat för manuell granskning (' . $type . ').',
        ];
    }

    /** @param array<string,mixed> $snapshot
     *  @return array<string,mixed>|null
     */
    private function callOpenAi(string $apiKey, string $type, array $snapshot): ?array
    {
        $payload = [
            'model' => 'gpt-4o-mini',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du hjälper en svensk e-handelsadmin att förbättra produktutkast. Returnera ENDAST JSON med fält: suggested_title, suggested_short_description, suggested_description, suggested_attributes, suggested_seo_title, suggested_meta_description, ai_summary. Inga påhittade fakta.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'suggestion_type' => $type,
                        'snapshot' => $snapshot,
                        'rules' => [
                            'review_first' => true,
                            'no_autopublish' => true,
                            'preserve_facts' => true,
                            'language' => 'sv',
                        ],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ],
            'temperature' => 0.2,
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

    /** @param array<string,mixed> $generated */
    private function isSuggestionEmpty(array $generated): bool
    {
        return trim((string) ($generated['suggested_title'] ?? '')) === ''
            && trim((string) ($generated['suggested_description'] ?? '')) === ''
            && trim((string) ($generated['suggested_seo_title'] ?? '')) === ''
            && trim((string) ($generated['suggested_meta_description'] ?? '')) === ''
            && trim((string) ($generated['suggested_attributes'] ?? '')) === '';
    }

    private function toNullableString(mixed $value, ?int $maxLength = null): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if ($maxLength !== null) {
            $text = mb_substr($text, 0, $maxLength);
        }

        return $text;
    }

    private function normalizeSuggestedAttributes(mixed $value): ?string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: null;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: null;
            }

            return trim($value);
        }

        return null;
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
            'attributes' => $this->attributesTextFromProduct($product),
        ];

        if ((int) ($product['is_active'] ?? 0) === 1) {
            $payload['is_active'] = '1';
        }
        if ((int) ($product['is_search_hidden'] ?? 0) === 1) {
            $payload['is_search_hidden'] = '1';
        }
        if ((int) ($product['is_featured'] ?? 0) === 1) {
            $payload['is_featured'] = '1';
        }
        if ((int) ($product['is_indexable'] ?? 0) === 1) {
            $payload['is_indexable'] = '1';
        }
        if ((int) ($product['backorder_allowed'] ?? 0) === 1) {
            $payload['backorder_allowed'] = '1';
        }

        return $payload;
    }

    /** @param array<string,mixed> $product */
    private function attributesTextFromProduct(array $product): string
    {
        $rows = [];
        foreach (($product['attributes'] ?? []) as $attribute) {
            if (!is_array($attribute)) {
                continue;
            }

            $key = trim((string) ($attribute['attribute_key'] ?? ''));
            $value = trim((string) ($attribute['attribute_value'] ?? ''));
            if ($key === '' || $value === '') {
                continue;
            }

            $rows[] = $key . '|' . $value;
        }

        return implode(PHP_EOL, $rows);
    }

    private function attributesTextFromSuggestion(string $suggestedAttributes): string
    {
        $decoded = json_decode($suggestedAttributes, true);
        if (!is_array($decoded)) {
            return trim($suggestedAttributes);
        }

        $rows = [];
        foreach ($decoded as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $rows[] = trim((string) $subKey) . '|' . trim((string) $subValue);
                }
                continue;
            }

            $rows[] = trim((string) $key) . '|' . trim((string) $value);
        }

        return implode(PHP_EOL, array_filter($rows, static fn (string $row): bool => $row !== '|' && trim($row) !== ''));
    }
}
