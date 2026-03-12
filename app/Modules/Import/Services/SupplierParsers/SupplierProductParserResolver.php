<?php

declare(strict_types=1);

namespace App\Modules\Import\Services\SupplierParsers;

final class SupplierProductParserResolver
{
    /** @var array<int,SupplierProductParserInterface> */
    private array $parsers;

    /** @param array<int,SupplierProductParserInterface> $parsers */
    public function __construct(array $parsers)
    {
        $this->parsers = $parsers;
    }

    public function resolve(string $domain): ?SupplierProductParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supportsDomain($domain)) {
                return $parser;
            }
        }

        return null;
    }

    /** @return array<int,string> */
    public function supportedDomainsSummary(): array
    {
        $domains = [];
        foreach ($this->parsers as $parser) {
            $domains[] = $parser->getParserKey();
        }

        return $domains;
    }
}
