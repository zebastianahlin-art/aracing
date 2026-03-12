<?php

declare(strict_types=1);

namespace App\Modules\Import\Services\SupplierParsers;

interface SupplierProductParserInterface
{
    public function getParserKey(): string;

    public function getParserVersion(): string;

    public function supportsDomain(string $domain): bool;

    /**
     * @return array{
     *   ok:bool,
     *   fields:array<string,mixed>,
     *   raw_text:?string,
     *   metadata:array<string,mixed>,
     *   error:?string
     * }
     */
    public function parse(string $url, string $html): array;
}
