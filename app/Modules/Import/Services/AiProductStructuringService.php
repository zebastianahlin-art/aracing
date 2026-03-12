<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Core\Config\Config;

final class AiProductStructuringService
{
    public function __construct(private readonly Config $config)
    {
    }

    /** @param array<string,mixed> $extracted
     *  @return array{summary:string,payload:array<string,mixed>,notes:string,used_ai:bool}
     */
    public function structure(array $extracted): array
    {
        $heuristic = $this->heuristicStructure($extracted);
        $apiKey = trim((string) $this->config->get('ai.url_import.openai_api_key', ''));

        if ($apiKey === '') {
            return $heuristic + ['used_ai' => false, 'notes' => 'Ingen AI-nyckel konfigurerad, heuristisk tolkning användes.'];
        }

        $aiResult = $this->callOpenAi($apiKey, $extracted['raw_text'] ?? '');
        if ($aiResult === null) {
            return $heuristic + ['used_ai' => false, 'notes' => 'AI-anrop misslyckades, heuristisk tolkning användes.'];
        }

        return [
            'summary' => (string) ($aiResult['summary'] ?? $heuristic['summary']),
            'payload' => is_array($aiResult['payload'] ?? null) ? $aiResult['payload'] : $heuristic['payload'],
            'notes' => (string) ($aiResult['notes'] ?? 'AI-strukturering lyckades.'),
            'used_ai' => true,
        ];
    }

    /** @param array<string,mixed> $extracted
     *  @return array{summary:string,payload:array<string,mixed>}
     */
    private function heuristicStructure(array $extracted): array
    {
        $text = (string) ($extracted['raw_text'] ?? '');
        $title = (string) ($extracted['title'] ?? '');
        $meta = (string) ($extracted['meta_description'] ?? '');

        preg_match('/\b([A-Z0-9][A-Z0-9\-]{3,30})\b/u', $text, $skuMatch);
        preg_match('/\b(\d+[\.,]\d{2})\s?(SEK|EUR|USD|kr)\b/ui', $text, $priceMatch);

        $price = null;
        $currency = null;
        if ($priceMatch !== []) {
            $price = (float) str_replace(',', '.', $priceMatch[1]);
            $currency = strtoupper($priceMatch[2]) === 'KR' ? 'SEK' : strtoupper($priceMatch[2]);
        }

        $brand = null;
        if (preg_match('/\b(Märke|Brand)\s*[:\-]\s*([^\|,\.]{2,60})/ui', $text, $brandMatch) === 1) {
            $brand = trim($brandMatch[2]);
        }

        $payload = [
            'title' => $title,
            'brand' => $brand,
            'sku' => $skuMatch[1] ?? null,
            'short_description' => $meta !== '' ? $meta : mb_substr($text, 0, 220),
            'description' => mb_substr($text, 0, 4000),
            'price' => $price,
            'currency' => $currency,
            'stock_text' => null,
            'image_urls' => $extracted['images'] ?? [],
            'attributes' => [
                'meta_description' => $meta,
            ],
            'confidence' => 'low',
        ];

        $summary = $title !== ''
            ? 'Automatisk tolkning skapad för: ' . $title
            : 'Automatisk tolkning skapad från sidans råtext.';

        return ['summary' => $summary, 'payload' => $payload];
    }

    /** @return array<string,mixed>|null */
    private function callOpenAi(string $apiKey, string $rawText): ?array
    {
        $rawText = mb_substr($rawText, 0, 12000);

        $payload = [
            'model' => 'gpt-4o-mini',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'Extrahera produktfält defensivt till JSON: summary, notes, payload.{title,brand,sku,short_description,description,price,currency,stock_text,image_urls,attributes,confidence}.'],
                ['role' => 'user', 'content' => $rawText],
            ],
            'temperature' => 0.2,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        if ($ch === false) {
            return null;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            return null;
        }

        $decoded = json_decode((string) $response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            return null;
        }

        $json = json_decode($content, true);

        return is_array($json) ? $json : null;
    }
}
