<?php

declare(strict_types=1);

namespace App\Modules\Cms\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cms\Services\CmsHomeService;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Storefront\Services\SeoService;

final class CmsStorefrontController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly CmsHomeService $home,
        private readonly CmsPageService $pages,
        private readonly SeoService $seo
    ) {
    }

    public function home(): Response
    {
        $payload = $this->home->storefrontHomeData();
        $payload['infoPages'] = $this->pages->storefrontInfoPages();
        $payload['seo'] = [
            'title' => 'Start | A-Racing',
            'description' => null,
            'canonical' => $this->seo->forSearch('/')['canonical'],
            'robots' => 'index,follow',
        ];

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
            'seo' => $this->seo->forCmsPage($page, '/pages/' . rawurlencode($slug)),
        ]));
    }
}
