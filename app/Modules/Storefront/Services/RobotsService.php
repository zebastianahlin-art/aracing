<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Services;

final class RobotsService
{
    public function build(): string
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /search',
            'Disallow: /checkout',
            'Disallow: /cart',
            'Disallow: /account',
            'Allow: /',
            'Sitemap: ' . $this->absoluteUrl('/sitemap.xml'),
        ];

        return implode("\n", $lines) . "\n";
    }

    private function absoluteUrl(string $path): string
    {
        $normalizedPath = str_starts_with($path, '/') ? $path : '/' . ltrim($path, '/');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000'));

        return $scheme . '://' . $host . $normalizedPath;
    }
}
