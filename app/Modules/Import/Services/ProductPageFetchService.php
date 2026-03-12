<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

final class ProductPageFetchService
{
    /** @return array{ok:bool,body:string,status_code:int,content_type:string,error:?string} */
    public function fetch(string $url): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'body' => '', 'status_code' => 0, 'content_type' => '', 'error' => 'Kunde inte initiera URL-hämtning.'];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 18);
        curl_setopt($ch, CURLOPT_USERAGENT, 'A-Racing URL Import Bot/1.0 (+https://a-racing.local)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

        $body = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return [
                'ok' => false,
                'body' => '',
                'status_code' => $statusCode,
                'content_type' => $contentType,
                'error' => $error !== '' ? $error : 'Okänt nätverksfel vid hämtning.',
            ];
        }

        $ok = $statusCode >= 200 && $statusCode < 300;

        return [
            'ok' => $ok,
            'body' => (string) $body,
            'status_code' => $statusCode,
            'content_type' => $contentType,
            'error' => $ok ? null : 'Källsidan svarade med HTTP ' . $statusCode . '.',
        ];
    }
}
