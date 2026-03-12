<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Catalog\Repositories\CatalogRepository;
use App\Modules\Catalog\Repositories\SearchQueryAliasRepository;
use App\Modules\Catalog\Repositories\SearchQueryLogRepository;
use App\Modules\Catalog\Repositories\SearchQuerySuggestionRepository;

final class AiSearchInsightService
{
    /** @var array<string,string> */
    private array $dictionary = [
        'avgas' => 'exhaust',
        'dämpare' => 'exhaust',
        'fälg' => 'wheel',
        'fälgar' => 'wheels',
        'broms' => 'brake',
        'bromsar' => 'brakes',
        'turbo kit' => 'turbo',
    ];

    public function __construct(
        private readonly SearchQueryLogRepository $logs,
        private readonly SearchQuerySuggestionRepository $suggestions,
        private readonly SearchQueryAliasRepository $aliases,
        private readonly CatalogRepository $catalog,
    ) {
    }

    /** @return array<string,mixed> */
    public function insights(): array
    {
        $rows = $this->logs->aggregateProblematicQueries();
        $rowsWithSuggestions = [];

        foreach ($rows as $row) {
            $query = (string) ($row['normalized_query'] ?? '');
            $pending = $this->suggestions->listPendingForQuery($query);
            $rowsWithSuggestions[] = [
                'query' => $query,
                'sample_query' => (string) ($row['sample_query'] ?? $query),
                'search_count' => (int) ($row['search_count'] ?? 0),
                'zero_result_count' => (int) ($row['zero_result_count'] ?? 0),
                'low_result_count' => (int) ($row['low_result_count'] ?? 0),
                'avg_results' => round((float) ($row['avg_results'] ?? 0), 2),
                'last_searched_at' => (string) ($row['last_searched_at'] ?? ''),
                'pending_suggestions' => $pending,
            ];
        }

        return [
            'problematic_queries' => $rowsWithSuggestions,
            'suggestions' => $this->suggestions->listAll(),
        ];
    }

    /** @return array<string,int> */
    public function generateSuggestions(): array
    {
        $created = 0;
        $skipped = 0;

        $rows = $this->logs->aggregateProblematicQueries(100);
        $lexicon = $this->catalog->searchLexicon();
        $targets = array_merge($lexicon['categories'], $lexicon['brands'], $lexicon['products']);

        foreach ($rows as $row) {
            $query = (string) ($row['normalized_query'] ?? '');
            if ($query === '') {
                continue;
            }

            $candidates = $this->buildCandidates($query, $targets);
            foreach ($candidates as $candidate) {
                $exists = $this->suggestions->existsPending($query, $candidate['type'], $candidate['value']);
                if ($exists) {
                    $skipped++;
                    continue;
                }

                $this->suggestions->create([
                    'source_query' => $query,
                    'suggestion_type' => $candidate['type'],
                    'suggested_value' => $candidate['value'],
                    'explanation' => $candidate['explanation'],
                    'status' => 'pending',
                ]);
                $created++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function approveSuggestion(int $suggestionId, ?int $reviewedByUserId = null): void
    {
        $suggestion = $this->suggestions->find($suggestionId);
        if ($suggestion === null) {
            throw new \RuntimeException('Förslag hittades inte.');
        }
        if ((string) ($suggestion['status'] ?? '') !== 'pending') {
            throw new \RuntimeException('Endast pending-förslag kan godkännas.');
        }

        $source = trim((string) ($suggestion['source_query'] ?? ''));
        $target = trim((string) ($suggestion['suggested_value'] ?? ''));
        $type = trim((string) ($suggestion['suggestion_type'] ?? 'query_alias'));
        if ($source === '' || $target === '') {
            throw new \RuntimeException('Ogiltigt förslag.');
        }

        $this->aliases->upsertActive($source, $target, $type);
        $this->suggestions->markReviewed($suggestionId, 'approved', $reviewedByUserId);
    }

    public function rejectSuggestion(int $suggestionId, ?int $reviewedByUserId = null): void
    {
        $suggestion = $this->suggestions->find($suggestionId);
        if ($suggestion === null) {
            throw new \RuntimeException('Förslag hittades inte.');
        }
        if ((string) ($suggestion['status'] ?? '') !== 'pending') {
            throw new \RuntimeException('Endast pending-förslag kan avvisas.');
        }

        $this->suggestions->markReviewed($suggestionId, 'rejected', $reviewedByUserId);
    }

    /** @param array<int,string> $targets
     * @return array<int,array{type:string,value:string,explanation:string}>
     */
    private function buildCandidates(string $query, array $targets): array
    {
        $candidates = [];

        $singular = $this->singularize($query);
        if ($singular !== $query && $this->containsCaseInsensitive($targets, $singular)) {
            $candidates[] = [
                'type' => 'synonym',
                'value' => $singular,
                'explanation' => 'Singular/plural-variant matchar katalogterm.',
            ];
        }

        $plural = $this->pluralize($query);
        if ($plural !== $query && $this->containsCaseInsensitive($targets, $plural)) {
            $candidates[] = [
                'type' => 'synonym',
                'value' => $plural,
                'explanation' => 'Singular/plural-variant matchar katalogterm.',
            ];
        }

        if (isset($this->dictionary[$query])) {
            $value = $this->dictionary[$query];
            $candidates[] = [
                'type' => 'query_alias',
                'value' => $value,
                'explanation' => 'Svensk/engelsk termmatchning baserad på v1-regelordlista.',
            ];
        }

        $closest = $this->closestLexiconTerm($query, $targets);
        if ($closest !== null && $closest !== $query) {
            $candidates[] = [
                'type' => 'redirect_query',
                'value' => $closest,
                'explanation' => 'Närmsta matchning mot kategori/brand/produkttitel i katalogen.',
            ];
        }

        $unique = [];
        foreach ($candidates as $candidate) {
            $key = $candidate['type'] . '|' . mb_strtolower($candidate['value']);
            $unique[$key] = $candidate;
        }

        return array_values($unique);
    }

    private function singularize(string $query): string
    {
        if (mb_strlen($query) > 3 && mb_substr($query, -1) === 's') {
            return mb_substr($query, 0, -1);
        }

        return $query;
    }

    private function pluralize(string $query): string
    {
        if (!str_ends_with($query, 's')) {
            return $query . 's';
        }

        return $query;
    }

    /** @param array<int,string> $terms */
    private function containsCaseInsensitive(array $terms, string $needle): bool
    {
        $needle = mb_strtolower(trim($needle));
        foreach ($terms as $term) {
            if (mb_strtolower(trim((string) $term)) === $needle) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int,string> $terms */
    private function closestLexiconTerm(string $query, array $terms): ?string
    {
        $query = mb_strtolower(trim($query));
        if ($query === '') {
            return null;
        }

        $bestTerm = null;
        $bestDistance = PHP_INT_MAX;
        foreach ($terms as $term) {
            $candidate = mb_strtolower(trim((string) $term));
            if ($candidate === '' || abs(mb_strlen($candidate) - mb_strlen($query)) > 8) {
                continue;
            }

            $distance = levenshtein($query, $candidate);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestTerm = $term;
            }
        }

        if ($bestTerm === null || $bestDistance > 3) {
            return null;
        }

        return mb_substr((string) $bestTerm, 0, 255);
    }
}
