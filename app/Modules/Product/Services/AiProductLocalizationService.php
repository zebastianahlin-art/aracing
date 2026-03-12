<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Core\Config\Config;
use App\Modules\Import\Repositories\AiProductImportDraftRepository;
use App\Modules\Product\Repositories\AiProductEnrichmentSuggestionRepository;
use InvalidArgumentException;

final class AiProductLocalizationService
{
    private const SUGGESTION_TYPE = 'swedish_localization';

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
        $this->assertSufficientInput($snapshot);

        $generated = $this->generateSuggestionPayload($snapshot);
        $title = $this->toNullableString($generated['suggested_title'] ?? null, 255);
        $short = $this->toNullableString($generated['suggested_short_description'] ?? null);
        $description = $this->toNullableString($generated['suggested_description'] ?? null);

        if ($this->isWeakSuggestion($title, $short, $description, $snapshot)) {
            throw new InvalidArgumentException('AI-svensk lokalisering gav inget meningsfullt förslag att granska.');
        }

        return $this->suggestions->create([
            'product_id' => $productId,
            'suggestion_type' => self::SUGGESTION_TYPE,
            'source_context' => (string) ($snapshot['source_context'] ?? 'manual'),
            'input_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'suggested_title' => $title,
            'suggested_short_description' => $short,
            'suggested_description' => $description,
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
            throw new InvalidArgumentException('Lokaliseringsförslaget hittades inte.');
        }

        if ((string) ($suggestion['suggestion_type'] ?? '') !== self::SUGGESTION_TYPE) {
            throw new InvalidArgumentException('Fel suggestion_type för svensk lokalisering.');
        }

        if ((string) ($suggestion['status'] ?? '') !== 'pending') {
            throw new InvalidArgumentException('Endast pending-lokaliseringsförslag kan appliceras.');
        }

        $productId = (int) ($suggestion['product_id'] ?? 0);
        $product = $this->products->get($productId);
        if ($product === null) {
            throw new InvalidArgumentException('Produkten för lokaliseringsförslaget hittades inte.');
        }

        $payload = $this->buildProductUpdatePayload($product);

        $title = $this->toNullableString($suggestion['suggested_title'] ?? null, 255);
        $description = $this->toNullableString($suggestion['suggested_description'] ?? null);
        $short = $this->toNullableString($suggestion['suggested_short_description'] ?? null);

        if ($title !== null) {
            $payload['name'] = $title;
        }
        if ($description !== null) {
            $payload['description'] = $description;
        } elseif ($short !== null && trim((string) ($payload['description'] ?? '')) === '') {
            $payload['description'] = $short;
        }

        if ($payload['name'] === '' && trim((string) ($payload['description'] ?? '')) === '') {
            throw new InvalidArgumentException('Lokaliseringsförslaget saknar applicerbar text.');
        }

        $this->products->update($productId, $payload);

        if ($this->suggestions->markApplied($suggestionId, $reviewedByUserId) === false) {
            throw new InvalidArgumentException('Lokaliseringsförslaget kunde inte markeras som applicerat.');
        }
    }

    public function rejectSuggestion(int $suggestionId, ?int $reviewedByUserId = null): void
    {
        $suggestion = $this->suggestions->findById($suggestionId);
        if ($suggestion === null) {
            throw new InvalidArgumentException('Lokaliseringsförslaget hittades inte.');
        }

        if ((string) ($suggestion['suggestion_type'] ?? '') !== self::SUGGESTION_TYPE) {
            throw new InvalidArgumentException('Fel suggestion_type för svensk lokalisering.');
        }

        if ((string) ($suggestion['status'] ?? '') !== 'pending') {
            throw new InvalidArgumentException('Endast pending-lokaliseringsförslag kan avvisas.');
        }

        if ($this->suggestions->markRejected($suggestionId, $reviewedByUserId) === false) {
            throw new InvalidArgumentException('Lokaliseringsförslaget kunde inte avvisas.');
        }
    }

    /** @param array<string,mixed> $product
     * @return array<string,mixed>
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
            'short_description' => $this->firstSentence((string) ($product['description'] ?? '')),
            'description' => trim((string) ($product['description'] ?? '')),
            'attributes' => $this->extractExistingAttributes($product),
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
            ] : null,
        ];
    }

    /** @param array<string,mixed> $snapshot */
    private function assertSufficientInput(array $snapshot): void
    {
        $joined = trim(implode(' ', array_filter([
            (string) ($snapshot['title'] ?? ''),
            (string) ($snapshot['description'] ?? ''),
            (string) (($snapshot['source_import_draft']['import_title'] ?? '') ?: ''),
            (string) (($snapshot['source_import_draft']['import_short_description'] ?? '') ?: ''),
            (string) (($snapshot['source_import_draft']['import_description'] ?? '') ?: ''),
        ])));

        if (mb_strlen($joined) < 24) {
            throw new InvalidArgumentException('För lite textunderlag för meningsfull svensk lokalisering. Lägg till mer produkttext först.');
        }
    }

    /** @param array<string,mixed> $snapshot
     * @return array<string,mixed>
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
            'suggested_title' => $aiPayload['suggested_title'] ?? $heuristic['suggested_title'],
            'suggested_short_description' => $aiPayload['suggested_short_description'] ?? $heuristic['suggested_short_description'],
            'suggested_description' => $aiPayload['suggested_description'] ?? $heuristic['suggested_description'],
            'ai_summary' => $aiPayload['ai_summary'] ?? $heuristic['ai_summary'],
        ];
    }

    /** @param array<string,mixed> $snapshot
     * @return array<string,mixed>
     */
    private function heuristicSuggestion(array $snapshot): array
    {
        $title = $this->toNullableString($snapshot['title'] ?? null, 255)
            ?? $this->toNullableString($snapshot['source_import_draft']['import_title'] ?? null, 255)
            ?? '';
        $short = $this->toNullableString($snapshot['source_import_draft']['import_short_description'] ?? null)
            ?? $this->toNullableString($this->firstSentence((string) ($snapshot['description'] ?? '')));
        $description = $this->toNullableString($snapshot['description'] ?? null)
            ?? $this->toNullableString($snapshot['source_import_draft']['import_description'] ?? null);

        $summary = 'Svensk lokalisering skapad med review-first. Kontrollera formulering och fakta före applicering.';
        if ($this->looksLikeSwedish($title . ' ' . $description)) {
            $summary = 'Texten verkar redan vara på svenska. Förslaget är främst en lätt copy-förbättring som ska granskas manuellt.';
        }

        return [
            'suggested_title' => $title,
            'suggested_short_description' => $short,
            'suggested_description' => $description,
            'ai_summary' => $summary,
        ];
    }

    /** @param array<string,mixed> $snapshot
     * @return array<string,mixed>|null
     */
    private function callOpenAi(string $apiKey, array $snapshot): ?array
    {
        $payload = [
            'model' => 'gpt-4o-mini',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du lokalanpassar produkttexter till naturlig svenska för e-handel. Returnera ENDAST JSON med: suggested_title, suggested_short_description, suggested_description, ai_summary. Använd bara fakta i snapshot. Hitta inte på tekniska fakta, fitment eller claims. Om underlaget redan är bra svenska: ge liten förbättring eller lämna fält tomma.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'task' => 'ai_product_translation_swedish_localization_v1',
                        'snapshot' => $snapshot,
                        'rules' => [
                            'review_first' => true,
                            'no_autopublish' => true,
                            'no_fitment_generation' => true,
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

    /** @param array<string,mixed> $product
     * @return array<string,string>
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

        foreach (['is_active', 'is_search_hidden', 'is_featured', 'is_indexable', 'backorder_allowed'] as $flag) {
            if ((int) ($product[$flag] ?? 0) === 1) {
                $payload[$flag] = '1';
            }
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
            if ($key !== '' && $value !== '') {
                $rows[] = $key . '|' . $value;
            }
        }

        return implode("\n", $rows);
    }

    /** @param array<string,mixed> $product
     * @return array<string,string>
     */
    private function extractExistingAttributes(array $product): array
    {
        $result = [];
        foreach (($product['attributes'] ?? []) as $attribute) {
            if (!is_array($attribute)) {
                continue;
            }

            $key = trim((string) ($attribute['attribute_key'] ?? ''));
            $value = trim((string) ($attribute['attribute_value'] ?? ''));
            if ($key !== '' && $value !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function firstSentence(string $text): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($normalized === '') {
            return '';
        }

        if (preg_match('/^(.{20,260}?[\.!?])\s/u', $normalized, $matches) === 1) {
            return trim((string) ($matches[1] ?? ''));
        }

        return mb_substr($normalized, 0, 220);
    }

    private function looksLikeSwedish(string $text): bool
    {
        $haystack = mb_strtolower($text);
        if ($haystack === '') {
            return false;
        }

        if (str_contains($haystack, 'å') || str_contains($haystack, 'ä') || str_contains($haystack, 'ö')) {
            return true;
        }

        foreach (['och', 'för', 'med', 'utan', 'passar', 'produkt'] as $token) {
            if (str_contains($haystack, $token)) {
                return true;
            }
        }

        return false;
    }

    private function isWeakSuggestion(?string $title, ?string $short, ?string $description, array $snapshot): bool
    {
        if ($title === null && $short === null && $description === null) {
            return true;
        }

        $sameTitle = $title !== null && trim((string) ($snapshot['title'] ?? '')) !== ''
            && mb_strtolower(trim((string) ($snapshot['title'] ?? ''))) === mb_strtolower($title);
        $sameDescription = $description !== null && trim((string) ($snapshot['description'] ?? '')) !== ''
            && mb_strtolower(trim((string) ($snapshot['description'] ?? ''))) === mb_strtolower($description);

        return $sameTitle && $sameDescription && $short === null;
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

        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if ($maxLength !== null) {
            $text = mb_substr($text, 0, $maxLength);
        }

        return $text;
    }
}
