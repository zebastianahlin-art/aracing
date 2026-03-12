<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Core\Config\Config;
use App\Modules\Brand\Services\BrandService;
use App\Modules\Category\Services\CategoryService;
use App\Modules\Import\Repositories\AiProductImportDraftRepository;
use App\Modules\Product\Repositories\AiProductEnrichmentSuggestionRepository;
use InvalidArgumentException;

final class AiProductCategorySuggestionService
{
    private const SUGGESTION_TYPE = 'category_suggestion';

    public function __construct(
        private readonly ProductService $products,
        private readonly CategoryService $categories,
        private readonly BrandService $brands,
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

        $pending = $this->suggestions->findPendingByProductAndType($productId, self::SUGGESTION_TYPE);
        if ($pending !== null) {
            throw new InvalidArgumentException('Produkten har redan ett pending kategoriförslag som måste reviewas först.');
        }

        $snapshot = $this->buildInputSnapshot($product);
        $generated = $this->generateSuggestionPayload($snapshot);

        $suggestedCategoryId = $this->resolveSuggestedCategoryId($generated['suggested_category_id'] ?? null);
        if ($suggestedCategoryId === null) {
            throw new InvalidArgumentException('AI kunde inte föreslå en giltig kategori.');
        }

        return $this->suggestions->create([
            'product_id' => $productId,
            'suggestion_type' => self::SUGGESTION_TYPE,
            'source_context' => (string) ($snapshot['source_context'] ?? 'manual'),
            'input_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'suggested_category_id' => $suggestedCategoryId,
            'ai_summary' => $this->toNullableString($generated['ai_summary'] ?? null) ?? 'AI-kategoriförslag skapades för manuell granskning.',
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
            throw new InvalidArgumentException('Kategoriförslaget hittades inte.');
        }

        if ((string) ($suggestion['suggestion_type'] ?? '') !== self::SUGGESTION_TYPE) {
            throw new InvalidArgumentException('Fel suggestion_type för kategoriapplicering.');
        }

        if ((string) ($suggestion['status'] ?? '') !== 'pending') {
            throw new InvalidArgumentException('Endast pending-kategoriförslag kan appliceras.');
        }

        $productId = (int) ($suggestion['product_id'] ?? 0);
        $product = $this->products->get($productId);
        if ($product === null) {
            throw new InvalidArgumentException('Produkten för kategoriförslaget hittades inte.');
        }

        $suggestedCategoryId = $this->resolveSuggestedCategoryId($suggestion['suggested_category_id'] ?? null);
        if ($suggestedCategoryId === null) {
            throw new InvalidArgumentException('Kategoriförslaget saknar giltig kategori.');
        }

        $payload = $this->buildProductUpdatePayload($product);
        $payload['category_id'] = (string) $suggestedCategoryId;

        $this->products->update($productId, $payload);

        if ($this->suggestions->markApplied($suggestionId, $reviewedByUserId) === false) {
            throw new InvalidArgumentException('Kategoriförslaget kunde inte markeras som applicerat.');
        }
    }

    public function rejectSuggestion(int $suggestionId, ?int $reviewedByUserId = null): void
    {
        $suggestion = $this->suggestions->findById($suggestionId);
        if ($suggestion === null) {
            throw new InvalidArgumentException('Kategoriförslaget hittades inte.');
        }

        if ((string) ($suggestion['suggestion_type'] ?? '') !== self::SUGGESTION_TYPE) {
            throw new InvalidArgumentException('Fel suggestion_type för kategoriavvisning.');
        }

        if ((string) ($suggestion['status'] ?? '') !== 'pending') {
            throw new InvalidArgumentException('Endast pending-kategoriförslag kan avvisas.');
        }

        if ($this->suggestions->markRejected($suggestionId, $reviewedByUserId) === false) {
            throw new InvalidArgumentException('Kategoriförslaget kunde inte avvisas.');
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

        $brand = null;
        if ((int) ($product['brand_id'] ?? 0) > 0) {
            $brand = $this->brands->get((int) $product['brand_id']);
        }

        $currentCategory = null;
        if ((int) ($product['category_id'] ?? 0) > 0) {
            $currentCategory = $this->categories->get((int) $product['category_id']);
        }

        return [
            'product_id' => (int) ($product['id'] ?? 0),
            'title' => trim((string) ($product['name'] ?? '')),
            'brand' => trim((string) ($brand['name'] ?? '')),
            'sku' => trim((string) ($product['sku'] ?? '')),
            'description' => trim((string) ($product['description'] ?? '')),
            'attributes' => $this->extractExistingAttributes($product),
            'source_url' => $product['source_url'] ?? null,
            'source_context' => $sourceContext,
            'source_import_draft' => $sourceDraft,
            'current_category' => $currentCategory !== null ? [
                'id' => (int) ($currentCategory['id'] ?? 0),
                'name' => (string) ($currentCategory['name'] ?? ''),
            ] : null,
            'candidate_categories' => $this->categories->listForSelect(),
        ];
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
        if ($aiPayload === null) {
            return $heuristic;
        }

        return [
            'suggested_category_id' => $aiPayload['suggested_category_id'] ?? $heuristic['suggested_category_id'],
            'ai_summary' => $aiPayload['ai_summary'] ?? $heuristic['ai_summary'],
        ];
    }

    /** @param array<string,mixed> $snapshot
     * @return array<string,mixed>
     */
    private function heuristicSuggestion(array $snapshot): array
    {
        $categoryId = null;
        $summary = 'AI föreslog kategori baserat på titel/beskrivning. Granska alltid manuellt innan applicering.';

        $categories = (array) ($snapshot['candidate_categories'] ?? []);
        $haystack = mb_strtolower(trim((string) (($snapshot['title'] ?? '') . ' ' . ($snapshot['description'] ?? ''))));

        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }

            $candidateId = isset($category['id']) ? (int) $category['id'] : 0;
            $candidateName = mb_strtolower(trim((string) ($category['name'] ?? '')));
            if ($candidateId <= 0 || $candidateName === '') {
                continue;
            }

            if ($haystack !== '' && str_contains($haystack, $candidateName)) {
                $categoryId = $candidateId;
                $summary = 'Träffade kategorinamnet "' . (string) $category['name'] . '" i produktdata. Granska manuellt före applicering.';
                break;
            }
        }

        if ($categoryId === null && $categories !== []) {
            $first = $categories[0];
            if (is_array($first) && isset($first['id'])) {
                $categoryId = (int) $first['id'];
                $summary = 'Ingen tydlig träff hittades; fallback-förslag sattes till första tillgängliga kategori för manuell review.';
            }
        }

        return [
            'suggested_category_id' => $categoryId,
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
                    'content' => 'Du hjälper en svensk e-handelsadmin att välja primär produktkategori. Returnera ENDAST JSON med fälten suggested_category_id och ai_summary. Föreslå exakt en befintlig kategori-id från listan. Skapa inte nya kategorier. Ingen fitmenttolkning. Ingen autopublicering.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'task' => 'ai_category_suggestion_v1',
                        'snapshot' => $snapshot,
                        'rules' => [
                            'review_first' => true,
                            'single_primary_category' => true,
                            'no_taxonomy_changes' => true,
                            'no_multi_category_assignment' => true,
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

    private function resolveSuggestedCategoryId(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '' || !ctype_digit((string) $value)) {
            return null;
        }

        $categoryId = (int) $value;

        return $this->categories->get($categoryId) !== null ? $categoryId : null;
    }

    private function toNullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
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

            $key = trim((string) ($attribute['attribute_key'] ?? ''));
            $value = trim((string) ($attribute['attribute_value'] ?? ''));
            if ($key === '' || $value === '') {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /** @param array<string,string> $attributes */
    private function attributesTextFromMap(array $attributes): string
    {
        if ($attributes === []) {
            return '';
        }

        $lines = [];
        foreach ($attributes as $key => $value) {
            $lines[] = trim($key) . '|' . trim($value);
        }

        return implode(PHP_EOL, $lines);
    }
}
