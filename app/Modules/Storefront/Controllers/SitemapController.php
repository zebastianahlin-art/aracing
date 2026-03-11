<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Controllers;

use App\Core\Http\Response;
use App\Modules\Storefront\Services\RobotsService;
use App\Modules\Storefront\Services\SitemapService;

final class SitemapController
{
    public function __construct(
        private readonly SitemapService $sitemaps,
        private readonly RobotsService $robots
    ) {
    }

    public function index(): Response
    {
        return new Response($this->sitemaps->sitemapIndexXml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function products(): Response
    {
        return new Response($this->sitemaps->productSitemapXml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function categories(): Response
    {
        return new Response($this->sitemaps->categorySitemapXml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function pages(): Response
    {
        return new Response($this->sitemaps->pageSitemapXml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        return new Response($this->robots->build(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
