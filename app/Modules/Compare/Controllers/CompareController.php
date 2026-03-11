<?php

declare(strict_types=1);

namespace App\Modules\Compare\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Compare\Services\CompareService;
use App\Modules\Storefront\Services\SeoService;
use InvalidArgumentException;
use RuntimeException;

final class CompareController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly CmsPageService $pages,
        private readonly SeoService $seo,
        private readonly CompareService $compare
    ) {
    }

    public function index(): Response
    {
        $products = $this->compare->comparedProducts();

        return new Response($this->views->render('storefront.compare', [
            'products' => $products,
            'compareCount' => count($products),
            'maxCompareItems' => $this->compare->maxItems(),
            'message' => trim((string) ($_GET['message'] ?? '')),
            'error' => trim((string) ($_GET['error'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
            'seo' => $this->seo->forStaticPage('Jämför produkter', '/compare'),
        ]));
    }

    public function add(): Response
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $backTo = $this->sanitizeBackTo((string) ($_POST['back_to'] ?? '/compare'));

        try {
            $this->compare->addProduct($productId);

            return $this->redirect($this->withFeedback($backTo, 'Produkten lades till i jämförelsen.', false));
        } catch (InvalidArgumentException|RuntimeException $e) {
            return $this->redirect($this->withFeedback($backTo, $e->getMessage(), true));
        }
    }

    public function remove(): Response
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $backTo = $this->sanitizeBackTo((string) ($_POST['back_to'] ?? '/compare'));

        try {
            $this->compare->removeProduct($productId);

            return $this->redirect($this->withFeedback($backTo, 'Produkten togs bort från jämförelsen.', false));
        } catch (InvalidArgumentException $e) {
            return $this->redirect($this->withFeedback($backTo, $e->getMessage(), true));
        }
    }


    private function withFeedback(string $backTo, string $text, bool $error): string
    {
        $isProductPage = str_starts_with($backTo, '/product/');
        $key = $error
            ? ($isProductPage ? 'compare_error' : 'error')
            : ($isProductPage ? 'compare_message' : 'message');

        return $backTo . $this->separator($backTo) . $key . '=' . urlencode($text);
    }
    private function sanitizeBackTo(string $backTo): string
    {
        $path = trim($backTo);
        if ($path === '' || !str_starts_with($path, '/')) {
            return '/compare';
        }

        if (str_starts_with($path, '//')) {
            return '/compare';
        }

        return $path;
    }

    private function separator(string $url): string
    {
        return str_contains($url, '?') ? '&' : '?';
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
