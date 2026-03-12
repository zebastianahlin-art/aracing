<?php

declare(strict_types=1);

namespace App\Modules\Product\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Brand\Services\BrandService;
use App\Modules\Category\Services\CategoryService;
use App\Modules\Product\Services\ProductService;
use App\Modules\Product\Services\ProductRelationService;
use App\Modules\Product\Services\ProductMediaService;
use App\Modules\Product\Services\ProductSupplierLinkService;
use App\Modules\Product\Services\AiProductEnrichmentService;
use App\Modules\Product\Services\AiProductAttributeSuggestionService;
use App\Modules\Product\Services\AiProductCategorySuggestionService;
use App\Modules\Product\Services\AiProductLocalizationService;
use App\Modules\Supplier\Services\SupplierService;
use App\Modules\Fitment\Services\ProductFitmentService;
use InvalidArgumentException;

final class ProductAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly ProductService $products,
        private readonly ProductMediaService $media,
        private readonly ProductRelationService $relations,
        private readonly BrandService $brands,
        private readonly CategoryService $categories,
        private readonly SupplierService $suppliers,
        private readonly ProductSupplierLinkService $productSupplierLinks,
        private readonly ProductFitmentService $fitments,
        private readonly AiProductEnrichmentService $enrichment,
        private readonly AiProductAttributeSuggestionService $attributeSuggestions,
        private readonly AiProductCategorySuggestionService $categorySuggestions,
        private readonly AiProductLocalizationService $localizationSuggestions,
    ) {
    }

    public function index(): Response
    {
        $overview = $this->products->operationalOverview($_GET);

        return new Response($this->views->render('admin.products.index', [
            'products' => $overview['rows'],
            'filters' => $overview['filters'],
            'notice' => (string) ($_GET['notice'] ?? ''),
        ]));
    }

    public function runProductAction(string $id): Response
    {
        $productId = (int) $id;
        $action = (string) ($_POST['action'] ?? '');

        try {
            if ($action === 'sync_snapshot') {
                $this->products->syncPrimarySnapshot($productId);
            }

            if ($action === 'copy_price') {
                $this->products->copySupplierPriceToPublished($productId);
            }

            if ($action === 'copy_stock') {
                $this->products->copySupplierStockToPublished($productId);
            }

            if ($action === 'refresh_stock_status') {
                $this->products->refreshPublishedStockStatusFromQuantity($productId);
            }

            if ($action === 'set_active') {
                $this->products->setActiveStatus($productId, true);
            }

            if ($action === 'set_inactive') {
                $this->products->setActiveStatus($productId, false);
            }

            if ($action === 'manual_adjust_stock') {
                $this->products->manualStockAdjustment($productId, $_POST);
            }

            return $this->redirect('/admin/products?notice=' . urlencode('Produktåtgärd sparad'));
        } catch (\Throwable $exception) {
            return $this->redirect('/admin/products?notice=' . urlencode('Lageråtgärd misslyckades: ' . $exception->getMessage()));
        }
    }

    public function runBulkAction(): Response
    {
        $action = (string) ($_POST['bulk_action'] ?? '');
        $selected = $_POST['selected_product_ids'] ?? [];
        $ids = [];

        if (is_array($selected)) {
            foreach ($selected as $id) {
                $normalized = trim((string) $id);
                if ($normalized !== '' && ctype_digit($normalized)) {
                    $ids[] = (int) $normalized;
                }
            }
        }

        if ($ids !== [] && $action !== '') {
            $this->products->applyBulkOperation($ids, $action);
        }

        return $this->redirect('/admin/products?notice=' . urlencode('Bulkåtgärd körd'));
    }

    public function createForm(): Response
    {
        $selectedSupplierId = $this->toNullableInt($_GET['supplier_id'] ?? null);
        $supplierItemQuery = trim((string) ($_GET['supplier_item_query'] ?? ''));
        $draft = null;
        $supplierItemId = $this->toNullableInt($_GET['supplier_item_id'] ?? null);

        if ($supplierItemId !== null) {
            $draft = $this->products->prefillDraftFromSupplierItem($supplierItemId);
            if ($draft !== null && $selectedSupplierId === null) {
                $selectedSupplierId = (int) ($draft['supplier_item']['supplier_id'] ?? 0) ?: null;
            }
        }

        return new Response($this->views->render('admin.products.form', [
            'product' => $draft['product_defaults'] ?? null,
            'prefill_draft' => $draft,
            'brands' => $this->brands->list(),
            'categories' => $this->categories->listForSelect(),
            'suppliers' => $this->suppliers->listActive(),
            'selected_supplier_id' => $selectedSupplierId,
            'supplier_item_query' => $supplierItemQuery,
            'supplier_items' => $this->productSupplierLinks->searchSupplierItems($selectedSupplierId, $supplierItemQuery),
            'stock_movements' => [],
            'product_fitments' => [],
            'fitment_vehicles' => $this->fitments->activeVehicles(trim((string) ($_GET['fitment_vehicle_query'] ?? ''))),
            'fitment_types' => $this->fitments->allowedTypes(),
            'fitment_vehicle_query' => trim((string) ($_GET['fitment_vehicle_query'] ?? '')),
            'ai_suggestions' => [],
            'ai_attribute_suggestions' => [],
            'ai_seo_suggestions' => [],
            'ai_category_suggestions' => [],
            'ai_localization_suggestions' => [],
        ]));
    }

    public function store(): Response
    {
        $productId = $this->products->create($_POST);

        $returnToReview = isset($_POST['return_to_review']) && (int) $_POST['return_to_review'] === 1;
        if ($returnToReview) {
            return $this->redirect('/admin/supplier-item-review?notice=' . urlencode('Produkt skapad och kopplad (#' . $productId . ')'));
        }

        return $this->redirect('/admin/products');
    }

    public function articleCareQueue(): Response
    {
        $queue = $this->products->articleCareQueue($_GET);

        return new Response($this->views->render('admin.products.article_care_queue', [
            'rows' => $queue['rows'],
            'filters' => $queue['filters'],
        ]));
    }

    public function editForm(string $id): Response
    {
        $product = $this->products->get((int) $id);
        $productId = (int) ($product['id'] ?? 0);
        $selectedSupplierId = $this->toNullableInt($_GET['supplier_id'] ?? ($product['primary_supplier_link']['supplier_id'] ?? null));
        $supplierItemQuery = trim((string) ($_GET['supplier_item_query'] ?? ''));
        $relationQuery = trim((string) ($_GET['relation_query'] ?? ''));
        $allSuggestions = $productId > 0 ? $this->enrichment->listForProduct($productId) : [];
        $aiSuggestions = array_values(array_filter($allSuggestions, static fn (array $suggestion): bool => !in_array((string) ($suggestion['suggestion_type'] ?? ''), ['seo_metadata', 'attribute_summary'], true)));

        return new Response($this->views->render('admin.products.form', [
            'product' => $product,
            'brands' => $this->brands->list(),
            'categories' => $this->categories->listForSelect(),
            'suppliers' => $this->suppliers->listActive(),
            'selected_supplier_id' => $selectedSupplierId,
            'supplier_item_query' => $supplierItemQuery,
            'supplier_items' => $this->productSupplierLinks->searchSupplierItems($selectedSupplierId, $supplierItemQuery),
            'stock_movements' => [],
            'relation_query' => $relationQuery,
            'relation_candidates' => $productId > 0 ? $this->products->searchForRelationSelection($relationQuery, $productId) : [],
            'product_relations' => $product !== null ? $this->relations->adminRelationsForProduct($productId) : [],
            'relation_types' => $this->relations->allowedTypes(),
            'product_fitments' => $product !== null ? $this->fitments->listForProduct($productId) : [],
            'fitment_vehicles' => $this->fitments->activeVehicles(trim((string) ($_GET['fitment_vehicle_query'] ?? ''))),
            'fitment_types' => $this->fitments->allowedTypes(),
            'fitment_vehicle_query' => trim((string) ($_GET['fitment_vehicle_query'] ?? '')),
            'ai_suggestions' => $aiSuggestions,
            'ai_attribute_suggestions' => $productId > 0 ? $this->attributeSuggestions->listForProduct($productId) : [],
            'ai_seo_suggestions' => $productId > 0 ? $this->enrichment->listSeoSuggestionsForProduct($productId) : [],
            'ai_category_suggestions' => $productId > 0 ? $this->categorySuggestions->listForProduct($productId) : [],
            'ai_localization_suggestions' => $productId > 0 ? $this->localizationSuggestions->listForProduct($productId) : [],
        ]));
    }

    public function update(string $id): Response
    {
        $this->products->update((int) $id, $_POST);

        return $this->redirect('/admin/products');
    }

    public function createEnrichmentSuggestion(string $id): Response
    {
        $productId = (int) $id;

        try {
            $suggestionId = $this->enrichment->createSuggestionForProduct($productId, (string) ($_POST['suggestion_type'] ?? ''), null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI-förslag #' . $suggestionId . ' skapades och väntar på granskning.') . '#ai-enrichment');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-enrichment');
        }
    }

    public function applyEnrichmentSuggestion(string $id, string $suggestionId): Response
    {
        $productId = (int) $id;

        try {
            $this->enrichment->applySuggestion((int) $suggestionId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI-förslaget applicerades på produktutkastet.') . '#ai-enrichment');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-enrichment');
        }
    }

    public function rejectEnrichmentSuggestion(string $id, string $suggestionId): Response
    {
        $productId = (int) $id;

        try {
            $this->enrichment->rejectSuggestion((int) $suggestionId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI-förslaget avvisades.') . '#ai-enrichment');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-enrichment');
        }
    }

    public function createAttributeSuggestion(string $id): Response
    {
        $productId = (int) $id;

        try {
            $suggestionId = $this->attributeSuggestions->createSuggestionForProduct($productId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI attributförslag #' . $suggestionId . ' skapades och väntar på granskning.') . '#ai-attribute-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-attribute-suggestions');
        }
    }

    public function applyAttributeSuggestion(string $id, string $suggestionId): Response
    {
        $productId = (int) $id;

        try {
            $this->attributeSuggestions->applySuggestion((int) $suggestionId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI-attributförslaget applicerades på produktens attributfält.') . '#ai-attribute-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-attribute-suggestions');
        }
    }

    public function rejectAttributeSuggestion(string $id, string $suggestionId): Response
    {
        $productId = (int) $id;

        try {
            $this->attributeSuggestions->rejectSuggestion((int) $suggestionId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI-attributförslaget avvisades.') . '#ai-attribute-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-attribute-suggestions');
        }
    }

    public function createSeoSuggestion(string $id): Response
    {
        $productId = (int) $id;

        try {
            $suggestionId = $this->enrichment->createSuggestionForProduct($productId, 'seo_metadata', null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI SEO-förslag #' . $suggestionId . ' skapades och väntar på granskning.') . '#ai-seo-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-seo-suggestions');
        }
    }

    public function applySeoSuggestion(string $id, string $suggestionId): Response
    {
        $productId = (int) $id;

        try {
            $this->enrichment->applySuggestion((int) $suggestionId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI SEO-förslaget applicerades på SEO-fälten.') . '#ai-seo-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-seo-suggestions');
        }
    }

    public function rejectSeoSuggestion(string $id, string $suggestionId): Response
    {
        $productId = (int) $id;

        try {
            $this->enrichment->rejectSuggestion((int) $suggestionId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI SEO-förslaget avvisades.') . '#ai-seo-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-seo-suggestions');
        }
    }


    public function createCategorySuggestion(string $id): Response
    {
        $productId = (int) $id;

        try {
            $suggestionId = $this->categorySuggestions->createSuggestionForProduct($productId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI-kategoriförslag #' . $suggestionId . ' skapades och väntar på granskning.') . '#ai-category-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-category-suggestions');
        }
    }

    public function applyCategorySuggestion(string $id, string $suggestionId): Response
    {
        $productId = (int) $id;

        try {
            $this->categorySuggestions->applySuggestion((int) $suggestionId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI-kategoriförslaget applicerades på produktens primära kategori.') . '#ai-category-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-category-suggestions');
        }
    }

    public function rejectCategorySuggestion(string $id, string $suggestionId): Response
    {
        $productId = (int) $id;

        try {
            $this->categorySuggestions->rejectSuggestion((int) $suggestionId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI-kategoriförslaget avvisades.') . '#ai-category-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-category-suggestions');
        }
    }

    public function createLocalizationSuggestion(string $id): Response
    {
        $productId = (int) $id;

        try {
            $suggestionId = $this->localizationSuggestions->createSuggestionForProduct($productId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI svensk lokalisering #' . $suggestionId . ' skapades och väntar på granskning.') . '#ai-localization-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-localization-suggestions');
        }
    }

    public function applyLocalizationSuggestion(string $id, string $suggestionId): Response
    {
        $productId = (int) $id;

        try {
            $this->localizationSuggestions->applySuggestion((int) $suggestionId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI svensk lokalisering applicerades på produktens textfält.') . '#ai-localization-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-localization-suggestions');
        }
    }

    public function rejectLocalizationSuggestion(string $id, string $suggestionId): Response
    {
        $productId = (int) $id;

        try {
            $this->localizationSuggestions->rejectSuggestion((int) $suggestionId, null);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('AI svensk lokalisering avvisades.') . '#ai-localization-suggestions');
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($e->getMessage()) . '#ai-localization-suggestions');
        }
    }

    public function uploadImages(string $id): Response
    {
        $productId = (int) $id;

        try {
            $count = $this->media->uploadImages($productId, $_FILES, (string) ($_POST['default_alt_text'] ?? ''));

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode($count . ' bild(er) uppladdade') . '#media');
        } catch (\RuntimeException $exception) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($exception->getMessage()) . '#media');
        }
    }

    public function updateImage(string $id, string $imageId): Response
    {
        $productId = (int) $id;
        $isPrimary = ((string) ($_POST['is_primary'] ?? '0')) === '1';

        try {
            $this->media->updateImageMeta(
                $productId,
                (int) $imageId,
                (string) ($_POST['alt_text'] ?? ''),
                (int) ($_POST['sort_order'] ?? 0),
                $isPrimary
            );

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('Bild metadata sparad') . '#media');
        } catch (\RuntimeException $exception) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($exception->getMessage()) . '#media');
        }
    }

    public function setPrimaryImage(string $id, string $imageId): Response
    {
        $productId = (int) $id;

        try {
            $this->media->setPrimaryImage($productId, (int) $imageId);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('Primärbild uppdaterad') . '#media');
        } catch (\RuntimeException $exception) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($exception->getMessage()) . '#media');
        }
    }

    public function deleteImage(string $id, string $imageId): Response
    {
        $productId = (int) $id;

        $this->media->deleteImage($productId, (int) $imageId);

        return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('Bild borttagen') . '#media');
    }

    public function createRelation(string $id): Response
    {
        $productId = (int) $id;

        try {
            $this->relations->createForProduct($productId, $_POST);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('Produktkoppling sparad') . '#relations');
        } catch (\RuntimeException $exception) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($exception->getMessage()) . '#relations');
        }
    }

    public function updateRelation(string $id, string $relationId): Response
    {
        $productId = (int) $id;

        try {
            $this->relations->updateForProduct($productId, (int) $relationId, $_POST);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('Produktkoppling uppdaterad') . '#relations');
        } catch (\RuntimeException $exception) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($exception->getMessage()) . '#relations');
        }
    }

    public function deleteRelation(string $id, string $relationId): Response
    {
        $productId = (int) $id;
        $this->relations->deleteForProduct($productId, (int) $relationId);

        return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('Produktkoppling borttagen') . '#relations');
    }


    public function createFitment(string $id): Response
    {
        $productId = (int) $id;

        try {
            $this->fitments->addToProduct($productId, $_POST);

            return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('Fordonskoppling sparad') . '#fitment');
        } catch (\RuntimeException $exception) {
            return $this->redirect('/admin/products/' . $productId . '/edit?media_error=' . urlencode($exception->getMessage()) . '#fitment');
        }
    }

    public function deleteFitment(string $id, string $fitmentId): Response
    {
        $productId = (int) $id;
        $this->fitments->deleteFromProduct($productId, (int) $fitmentId);

        return $this->redirect('/admin/products/' . $productId . '/edit?notice=' . urlencode('Fordonskoppling borttagen') . '#fitment');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '' || ctype_digit($normalized) === false) {
            return null;
        }

        return (int) $normalized;
    }
}
