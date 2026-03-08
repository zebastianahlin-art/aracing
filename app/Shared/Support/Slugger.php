<?php

declare(strict_types=1);

namespace App\Shared\Support;

final class Slugger
{
    public static function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'item';
    }
}
