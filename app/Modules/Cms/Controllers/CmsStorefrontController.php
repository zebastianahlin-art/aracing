<?php

declare(strict_types=1);

namespace App\Modules\Cms\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cms\Services\CmsHomeService;
use App\Modules\Cms\Services\CmsPageService;

final class CmsStorefrontController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly CmsHomeService $home,
        private readonly CmsPageService $pages
    ) {
    }

    public function home(): Response
    {
        $payload = $this->home->storefrontHomeData();
        $payload['infoPages'] = $this->pages->storefrontInfoPages();

        return new Response($this->views->render('storefront.home', $payload));
    }

    public function page(string $slug): Response
    {
        $page = $this->pages->getActiveBySlug($slug);
        if ($page === null) {
            return new Response('Sidan hittades inte.', 404, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        return new Response($this->views->render('storefront.page', [
            'page' => $page,
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }
}
