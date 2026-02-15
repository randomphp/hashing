<?php

namespace RandomPHP\Hashing\Abstract;

use RandomPHP\Hashing\Interface\HashingAlgorithmInterface;

abstract class HashingAlgorithmWithInstanceCache implements HashingAlgorithmInterface
{
    /**
     * @var static[]
     */
    private static array $instanceCache = [];

//    private static bool

    /**
     * This is to avoid having multiple of the same HashingAlgorithm
     *
     * @param static $item
     * @param bool $cache
     * @return static
     */
    final protected static function instanceCache(
        $item,
        bool $cache = true
    ): static {
        if (!$cache) {
            return $item;
        }
        foreach (self::$instanceCache as $algorithm) {
            if ($algorithm->match($item)) {
                return $algorithm;
            }
        }
        self::$instanceCache[] = $item;
        return $item;
    }
}