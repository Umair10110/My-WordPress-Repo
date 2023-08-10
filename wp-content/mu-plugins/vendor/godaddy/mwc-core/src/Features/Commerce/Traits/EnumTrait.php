<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits;

use ReflectionClass;

/**
 * Enables enum-like syntax pre PHP 8.1.
 *
 * @see https://www.php.net/manual/en/language.enumerations.backed.php
 */
trait EnumTrait
{
    /**
     * Maps a scalar to an enum value or null.
     *
     * @param int|string $value The scalar value to map to an enum case.
     *
     * @return static::*|null
     */
    public static function tryFrom($value)
    {
        return in_array($value, static::values(), true) ? $value : null;
    }

    /**
     * Fetches the values for this enum.
     *
     * @return array<static::*> An array of enum values.
     */
    public static function values() : array
    {
        return array_values(static::cases());
    }

    /**
     * Returns an associative array where the enum names are the keys and the enum values are the values.
     *
     * @return array<string, static::*>
     */
    public static function cases() : array
    {
        /** @var array<string, static::*> $cases */
        $cases = (new ReflectionClass(static::class))->getConstants();

        return $cases;
    }
}
