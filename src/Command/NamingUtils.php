<?php

declare(strict_types=1);

namespace Dbp\Relay\MakerBundle\Command;

class NamingUtils
{
    public static function plural(string $in): string
    {
        return $in.'s';
    }

    public static function normalize(string $in): string
    {
        return strtolower(str_replace(['-', '_'], ' ', $in));
    }

    public static function pascal(string $in): string
    {
        return str_replace(' ', '', ucwords(self::normalize($in)));
    }

    public static function kebap(string $in): string
    {
        return str_replace(' ', '-', self::normalize($in));
    }

    public static function snake(string $in): string
    {
        return str_replace(' ', '_', self::normalize($in));
    }
}
