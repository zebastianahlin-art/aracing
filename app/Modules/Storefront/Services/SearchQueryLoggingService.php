<?php

declare(strict_types=1);

namespace App\Modules\Storefront\Services;

use App\Modules\Catalog\Repositories\SearchQueryLogRepository;

final class SearchQueryLoggingService
{
    public function __construct(private readonly SearchQueryLogRepository $logs)
    {
    }

    public function logStorefrontSearch(string $queryText, int $resultCount): void
    {
        $queryText = trim($queryText);
        if ($queryText === '') {
            return;
        }

        $normalized = $this->normalize($queryText);
        $sessionId = session_id() !== '' ? session_id() : null;

        try {
            $this->logs->log($queryText, $normalized, $resultCount, $sessionId);
        } catch (\Throwable) {
            // Söket i storefront får aldrig fallera på grund av logging.
        }
    }

    private function normalize(string $query): string
    {
        $query = mb_strtolower(trim($query));
        $query = preg_replace('/\s+/', ' ', $query) ?? $query;

        return mb_substr($query, 0, 255);
    }
}
