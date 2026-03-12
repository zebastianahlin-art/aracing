<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

final class AiImportQualityService
{
    /** @param array<string,mixed> $draft */
    public function analyzeDraft(array $draft): array
    {
        $missingFields = $this->detectMissingFields($draft);
        $qualityFlags = $this->detectQualityFlags($draft, $missingFields);
        $qualityLabel = $this->resolveQualityLabel($missingFields, $qualityFlags);

        return [
            'quality_label' => $qualityLabel,
            'confidence_summary' => $this->buildConfidenceSummary($qualityLabel, $missingFields, $qualityFlags),
            'missing_fields' => $missingFields,
            'quality_flags' => $qualityFlags,
        ];
    }

    /** @param array<string,mixed> $draft @return array<int,string> */
    public function detectMissingFields(array $draft): array
    {
        $missing = [];

        if ($this->isBlank($draft['import_title'] ?? null)) {
            $missing[] = 'title';
        }

        if ($this->isBlank($draft['import_brand'] ?? null)) {
            $missing[] = 'brand';
        }

        if ($this->isBlank($draft['import_sku'] ?? null)) {
            $missing[] = 'sku';
        }

        if ($this->isBlank($draft['import_description'] ?? null)) {
            $missing[] = 'description';
        }

        $imageUrls = $this->decodeJsonArray($draft['import_image_urls'] ?? null);
        if ($imageUrls === []) {
            $missing[] = 'image_urls';
        }

        return $missing;
    }

    /** @param array<int,string> $missingFields @return array<int,string> */
    public function detectQualityFlags(array $draft, array $missingFields): array
    {
        $flags = [];
        $strategy = strtolower(trim((string) ($draft['extraction_strategy'] ?? '')));
        $parserKey = trim((string) ($draft['parser_key'] ?? ''));
        $rawText = trim((string) ($draft['import_raw_text'] ?? ''));

        if ($parserKey !== '') {
            $flags[] = 'parser_used';
        }

        if (str_contains($strategy, 'fallback')) {
            $flags[] = 'fallback_used';
        }

        if ($parserKey !== '' && $this->strategyUsesAi($strategy)) {
            $flags[] = 'parser_and_ai_combined';
        }

        if ($parserKey === '' && $this->strategyUsesAi($strategy)) {
            $flags[] = 'ai_only_no_parser';
        }

        if (strlen($rawText) < 250) {
            $flags[] = 'weak_raw_text';
        }

        $missingMap = array_flip($missingFields);
        if (isset($missingMap['title'])) {
            $flags[] = 'missing_title';
        }

        if (isset($missingMap['brand'])) {
            $flags[] = 'missing_brand';
        }

        if (isset($missingMap['sku'])) {
            $flags[] = 'missing_sku';
        }

        if (isset($missingMap['description'])) {
            $flags[] = 'missing_description';
        }

        if (isset($missingMap['image_urls'])) {
            $flags[] = 'missing_images';
        }

        return array_values(array_unique($flags));
    }

    /** @param array<int,string> $missingFields @param array<int,string> $qualityFlags */
    public function resolveQualityLabel(array $missingFields, array $qualityFlags): string
    {
        if (
            $missingFields === []
            && !in_array('weak_raw_text', $qualityFlags, true)
            && (
                in_array('parser_used', $qualityFlags, true)
                || in_array('parser_and_ai_combined', $qualityFlags, true)
                || in_array('ai_only_no_parser', $qualityFlags, true)
            )
            && !in_array('fallback_used', $qualityFlags, true)
        ) {
            return 'high';
        }

        if (count($missingFields) >= 3 || in_array('weak_raw_text', $qualityFlags, true)) {
            return 'low';
        }

        return 'medium';
    }

    /** @param array<int,string> $missingFields @param array<int,string> $qualityFlags */
    public function buildConfidenceSummary(string $qualityLabel, array $missingFields, array $qualityFlags): string
    {
        $parts = [];
        $parts[] = match ($qualityLabel) {
            'high' => 'Hög kvalitet: centrala fält finns och underlaget ser stabilt ut.',
            'medium' => 'Medelkvalitet: underlaget går att arbeta vidare med men kräver manuell kontroll.',
            default => 'Låg kvalitet: underlaget är tunt eller saknar flera nyckelfält.',
        };

        if ($missingFields !== []) {
            $parts[] = 'Saknade nyckelfält: ' . implode(', ', $missingFields) . '.';
        } else {
            $parts[] = 'Alla nyckelfält (title, brand, sku, description, image_urls) finns.';
        }

        if (in_array('fallback_used', $qualityFlags, true)) {
            $parts[] = 'Fallback-strategi användes och bör granskas extra noggrant.';
        } elseif (in_array('parser_and_ai_combined', $qualityFlags, true)) {
            $parts[] = 'Parser och AI användes tillsammans.';
        } elseif (in_array('parser_used', $qualityFlags, true)) {
            $parts[] = 'Specialparser användes för extraktion.';
        } elseif (in_array('ai_only_no_parser', $qualityFlags, true)) {
            $parts[] = 'Ingen specialparser användes; resultatet bygger på generisk AI-strukturering.';
        }

        if (in_array('weak_raw_text', $qualityFlags, true)) {
            $parts[] = 'Råtexten är kort och kan ge osäkrare tolkning.';
        }

        return implode(' ', $parts);
    }

    private function isBlank(mixed $value): bool
    {
        return trim((string) $value) === '';
    }

    private function strategyUsesAi(string $strategy): bool
    {
        return str_contains($strategy, 'ai');
    }

    /** @return array<int,mixed> */
    private function decodeJsonArray(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values(array_filter($decoded, static fn ($item): bool => trim((string) $item) !== '')) : [];
    }
}
