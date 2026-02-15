<?php

namespace RandomPHP\Hashing;

use RandomPHP\Hashing\Interface\HashingAlgorithmInterface;

/**
 * AlgoCache is a cache we use to prevent instantiating multiple
 * of the same HashingAlgorithm's with the same arguments.
 * But if the hash you decode is from an untrusted source,
 * you should disable this while instantiating otherwise,
 * it could result in a memory leak.
 */
final class AlgoCache
{
    /**
     * @var bool|null
     */
    private static bool $enabled = true;

    /**
     * @var HashingAlgorithmInterface[]
     */
    private static array $cache = [];

    private function __construct()
    {
    }

    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * @param bool $value
     * @return bool
     */
    public static function toggle(bool $value): bool
    {
        $oldValue = self::$enabled;
        self::$enabled = $value;
        return $oldValue;
    }

    /**
     * @template T
     * @param callable():T $callable
     * @return T
     */
    public static function disabled(callable $callable): mixed
    {
        $currentValue = self::$enabled;
        self::$enabled = false;
        try {
            return $callable();
        } finally {
            self::$enabled = $currentValue;
        }
    }

    /**
     * @template T
     * @param T $algorithm
     * @return T
     */
    public static function cache(mixed $algorithm): mixed
    {
        if (!self::$enabled || !($algorithm instanceof HashingAlgorithmInterface)) {
            return $algorithm;
        }
        foreach (self::$cache as $algo) {
            if ($algo->match($algorithm)) {
                return $algo;
            }
        }
        self::$cache[] = $algorithm;
        return $algorithm;
    }
}