<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Repositories\CatalogRepository;

final class CatalogService
{
    public function __construct(private readonly CatalogRepository $catalog)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function latestProducts(int $limit = 12): array
    {
        return $this->catalog->latestActiveProducts($limit);
    }

    /** @return array<string, mixed> */
    public function categoryPage(string $slug): array
    {
        $category = $this->catalog->categoryBySlug($slug);

        if ($category === null) {
            return ['category' => null, 'products' => []];
        }

        return [
            'category' => $category,
            'products' => $this->catalog->activeProductsByCategory((int) $category['id']),
        ];
    }

    /** @return array<string, mixed>|null */
    public function productPage(string $slug): ?array
    {
        $product = $this->catalog->activeProductBySlug($slug);

        if ($product === null) {
            return null;
        }

        $product['attributes'] = $this->catalog->productAttributes((int) $product['id']);
        $product['images'] = $this->catalog->productImages((int) $product['id']);

        return $product;
    }
}
