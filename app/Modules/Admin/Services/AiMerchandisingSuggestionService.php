<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Repositories\AiMerchandisingSuggestionRepository;
use App\Modules\Storefront\Repositories\HomepageSectionItemRepository;
use App\Modules\Storefront\Repositories\HomepageSectionRepository;
use PDO;

final class AiMerchandisingSuggestionService
{
    private const ALLOWED_TYPES = [
        'homepage_featured_products',
        'new_arrivals_collection',
        'fitment_recommended_collection',
        'supplier_high_priority_collection',
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly AiMerchandisingSuggestionRepository $suggestions,
        private readonly HomepageSectionRepository $homepageSections,
        private readonly HomepageSectionItemRepository $homepageItems,
    ) {
    }

    /** @return array{created:int, skipped:int} */
    public function buildSuggestions(): array
    {
        $excludedProductIds = $this->homepageProductIds();
        $created = 0;
        $skipped = 0;

        foreach ($this->buildCandidateDefinitions() as $definition) {
            if ($created >= 2) {
                break;
            }

            $productIds = array_values(array_filter(
                array_unique($definition['product_ids']),
                static fn (int $id): bool => $id > 0
            ));
            $productIds = array_values(array_filter($productIds, static fn (int $id): bool => !in_array($id, $excludedProductIds, true)));
            $productIds = array_slice($productIds, 0, 8);

            if (count($productIds) < 3) {
                ++$skipped;
                continue;
            }

            if ($this->hasPendingSuggestionForProducts($definition['type'], $productIds)) {
                ++$skipped;
                continue;
            }

            $this->suggestions->create([
                'suggestion_type' => $definition['type'],
                'title' => $definition['title'],
                'description' => $definition['description'],
                'suggested_product_ids' => json_encode($productIds, JSON_UNESCAPED_UNICODE),
                'context_snapshot' => json_encode($definition['context_snapshot'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'status' => 'pending',
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
            ]);
            ++$created;
            $excludedProductIds = array_values(array_unique(array_merge($excludedProductIds, $productIds)));
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** @return array<int,array<string,mixed>> */
    public function listSuggestions(?string $status = null): array
    {
        $rows = $this->suggestions->listAll($status);
        foreach ($rows as &$row) {
            $row = $this->hydrateSuggestion($row);
        }

        return $rows;
    }

    /** @return array<string,mixed>|null */
    public function findSuggestion(int $id): ?array
    {
        $row = $this->suggestions->findById($id);
        if ($row === null) {
            return null;
        }

        return $this->hydrateSuggestion($row);
    }

    public function approveSuggestion(int $id, ?int $reviewedByUserId = null): void
    {
        $suggestion = $this->requirePendingSuggestion($id);
        $productIds = $this->decodeIds($suggestion['suggested_product_ids'] ?? null);
        if ($productIds === []) {
            throw new \RuntimeException('Förslaget saknar giltiga produkter.');
        }

        $type = (string) ($suggestion['suggestion_type'] ?? 'homepage_featured_products');
        $title = trim((string) ($suggestion['title'] ?? 'AI merch-förslag'));
        $description = trim((string) ($suggestion['description'] ?? ''));

        $sectionType = 'featured_products';
        $sectionKey = sprintf('ai_merch_%s_%d', $type, (int) ($suggestion['id'] ?? 0));
        $sortOrder = $this->nextHomepageSortOrder();

        $this->pdo->beginTransaction();
        try {
            $sectionId = $this->homepageSections->create([
                'section_key' => substr(preg_replace('/[^a-z0-9_\-]+/', '_', strtolower($sectionKey)) ?? 'ai_merch', 0, 80),
                'title' => $title,
                'subtitle' => $description !== '' ? mb_substr($description, 0, 255) : null,
                'section_type' => $sectionType,
                'is_active' => 0,
                'sort_order' => $sortOrder,
                'max_items' => min(8, count($productIds)),
                'cta_label' => null,
                'cta_url' => null,
            ]);

            foreach ($productIds as $index => $productId) {
                $this->homepageItems->create([
                    'homepage_section_id' => $sectionId,
                    'item_type' => 'product',
                    'item_id' => $productId,
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => 1,
                ]);
            }

            $this->suggestions->updateStatus($id, 'approved', $reviewedByUserId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function rejectSuggestion(int $id, ?int $reviewedByUserId = null): void
    {
        $this->requirePendingSuggestion($id);
        $this->suggestions->updateStatus($id, 'rejected', $reviewedByUserId);
    }

    /** @return array<int,array<string,mixed>> */
    private function buildCandidateDefinitions(): array
    {
        return [
            [
                'type' => 'homepage_featured_products',
                'title' => 'AI: Populära produkter i lager',
                'description' => 'Baserat på toppsäljare senaste 30 dagarna med säkert lager.',
                'product_ids' => $this->topSellerProductIds(30),
                'context_snapshot' => ['source' => 'orders.last_30_days', 'rule' => 'top_sellers_in_stock'],
            ],
            [
                'type' => 'new_arrivals_collection',
                'title' => 'AI: Nya produkter med högt lager',
                'description' => 'Nya produkter prioriterade på tillgängligt lager för snabb exponering.',
                'product_ids' => $this->newArrivalProductIds(),
                'context_snapshot' => ['source' => 'products.created_at', 'rule' => 'new_arrivals_high_stock'],
            ],
            [
                'type' => 'fitment_recommended_collection',
                'title' => 'AI: Fitment-relevanta produkter',
                'description' => 'Produkter med bekräftade fitments och tillgängligt lager.',
                'product_ids' => $this->fitmentRelevantProductIds(),
                'context_snapshot' => ['source' => 'product_fitments', 'rule' => 'confirmed_fitment_and_stock'],
            ],
            [
                'type' => 'supplier_high_priority_collection',
                'title' => 'AI: Prioriterade leverantörsprodukter',
                'description' => 'Produkter kopplade till bevakade leverantörer/varumärken.',
                'product_ids' => $this->highPrioritySupplierProductIds(),
                'context_snapshot' => ['source' => 'monitored_supplier_entities', 'rule' => 'watchlist_priority_active'],
            ],
        ];
    }

    /** @return array<int,int> */
    private function topSellerProductIds(int $days): array
    {
        $stmt = $this->pdo->prepare('SELECT oi.product_id, SUM(oi.quantity) AS qty
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            INNER JOIN products p ON p.id = oi.product_id
            WHERE oi.product_id IS NOT NULL
              AND o.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
              AND p.stock_status <> "out_of_stock"
              AND COALESCE(p.stock_quantity, 0) > 0
            GROUP BY oi.product_id
            ORDER BY qty DESC
            LIMIT 20');
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn (array $row): int => (int) ($row['product_id'] ?? 0), $stmt->fetchAll());
    }

    /** @return array<int,int> */
    private function newArrivalProductIds(): array
    {
        $rows = $this->pdo->query('SELECT p.id
            FROM products p
            WHERE p.stock_status <> "out_of_stock"
              AND COALESCE(p.stock_quantity, 0) > 0
            ORDER BY p.created_at DESC, p.stock_quantity DESC, p.id DESC
            LIMIT 20')->fetchAll();

        return array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows);
    }

    /** @return array<int,int> */
    private function fitmentRelevantProductIds(): array
    {
        $rows = $this->pdo->query('SELECT p.id, COUNT(DISTINCT pf.vehicle_id) AS vehicle_count
            FROM products p
            INNER JOIN product_fitments pf ON pf.product_id = p.id
            WHERE p.stock_status <> "out_of_stock"
              AND COALESCE(p.stock_quantity, 0) > 0
              AND pf.fitment_type IN ("confirmed", "universal")
            GROUP BY p.id
            ORDER BY vehicle_count DESC, p.stock_quantity DESC, p.id DESC
            LIMIT 20')->fetchAll();

        return array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows);
    }

    /** @return array<int,int> */
    private function highPrioritySupplierProductIds(): array
    {
        $rows = $this->pdo->query('SELECT DISTINCT p.id
            FROM products p
            INNER JOIN product_supplier_links psl ON psl.product_id = p.id AND psl.is_primary = 1
            INNER JOIN supplier_items si ON si.id = psl.supplier_item_id
            LEFT JOIN monitored_supplier_entities ms_supplier
                ON ms_supplier.entity_type = "supplier"
                AND ms_supplier.entity_id = si.supplier_id
                AND ms_supplier.is_active = 1
            LEFT JOIN monitored_supplier_entities ms_brand
                ON ms_brand.entity_type = "brand"
                AND ms_brand.entity_id = p.brand_id
                AND ms_brand.is_active = 1
            WHERE p.stock_status <> "out_of_stock"
              AND COALESCE(p.stock_quantity, 0) > 0
              AND (ms_supplier.id IS NOT NULL OR ms_brand.id IS NOT NULL)
            ORDER BY p.stock_quantity DESC, p.updated_at DESC, p.id DESC
            LIMIT 20')->fetchAll();

        return array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows);
    }

    /** @return array<string,mixed> */
    private function hydrateSuggestion(array $suggestion): array
    {
        $productIds = $this->decodeIds($suggestion['suggested_product_ids'] ?? null);
        $suggestion['suggested_product_ids'] = $productIds;
        $suggestion['context_snapshot_decoded'] = $this->decodeJson($suggestion['context_snapshot'] ?? null);
        $suggestion['products'] = $this->productsByIds($productIds);

        return $suggestion;
    }

    /** @return array<int,array<string,mixed>> */
    private function productsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare('SELECT p.id,
                p.name,
                p.stock_status,
                p.stock_quantity,
                p.sale_price,
                p.currency_code,
                (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS image_url
            FROM products p
            WHERE p.id IN (' . $placeholders . ')');
        foreach ($ids as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $rowsById = [];
        foreach ($stmt->fetchAll() as $row) {
            $rowsById[(int) $row['id']] = $row;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($rowsById[$id])) {
                $ordered[] = $rowsById[$id];
            }
        }

        return $ordered;
    }

    /** @return array<int,int> */
    private function homepageProductIds(): array
    {
        $rows = $this->pdo->query('SELECT DISTINCT hsi.item_id
            FROM homepage_section_items hsi
            INNER JOIN homepage_sections hs ON hs.id = hsi.homepage_section_id
            WHERE hs.is_active = 1
              AND hsi.is_active = 1
              AND hsi.item_type = "product"')->fetchAll();

        return array_values(array_filter(array_map(static fn (array $row): int => (int) ($row['item_id'] ?? 0), $rows), static fn (int $id): bool => $id > 0));
    }

    private function nextHomepageSortOrder(): int
    {
        $max = 0;
        foreach ($this->homepageSections->all() as $section) {
            $max = max($max, (int) ($section['sort_order'] ?? 0));
        }

        return $max + 10;
    }

    private function hasPendingSuggestionForProducts(string $type, array $productIds): bool
    {
        foreach ($this->suggestions->listAll('pending') as $row) {
            if ((string) ($row['suggestion_type'] ?? '') !== $type) {
                continue;
            }

            $existing = $this->decodeIds($row['suggested_product_ids'] ?? null);
            if ($existing === $productIds) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string,mixed> */
    private function requirePendingSuggestion(int $id): array
    {
        $suggestion = $this->suggestions->findById($id);
        if ($suggestion === null) {
            throw new \RuntimeException('Förslaget kunde inte hittas.');
        }

        $status = (string) ($suggestion['status'] ?? '');
        if ($status !== 'pending') {
            throw new \RuntimeException('Endast pending-förslag kan granskas.');
        }

        $type = (string) ($suggestion['suggestion_type'] ?? '');
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new \RuntimeException('Ogiltig suggestion_type.');
        }

        return $suggestion;
    }

    /** @return array<int,int> */
    private function decodeIds(mixed $value): array
    {
        $decoded = $this->decodeJson($value);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn (mixed $id): int => (int) $id, $decoded), static fn (int $id): bool => $id > 0));
    }

    private function decodeJson(mixed $value): mixed
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
