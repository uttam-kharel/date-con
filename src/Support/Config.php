<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Support;

/**
 * Reads package config with graceful fallbacks, so the package also works
 * in plain PHP projects that have no Laravel container available.
 */
final class Config
{
    public static function get(string $key, mixed $default = null): mixed
    {
        if (function_exists('config')) {
            try {
                $value = config($key);

                return $value ?? $default;
            } catch (\Throwable) {
                // No Laravel container: fall through to the default.
            }
        }

        return $default;
    }
}
