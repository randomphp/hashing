<?php

namespace RandomPHP\Hashing\Interface;

interface HashInterface extends \JsonSerializable, \Stringable
{
    /**
     * @return HashingAlgorithmInterface
     */
    public function getAlgo(): HashingAlgorithmInterface;

    /**
     * @param HashingAlgorithmInterface $algorithm
     * @return bool
     */
    public function needsRehash(HashingAlgorithmInterface $algorithm): bool;

    /**
     * @param string $clearTest
     * @return bool
     */
    public function verify(string $clearTest): bool;

    /**
     * @param string $hash
     * @return static
     */
    public static function make(string $hash): HashInterface;

    /**
     * @return string
     */
    public function toString(): string;

    /**
     * @param string $hash
     * @return HashingAlgorithmInterface|null
     */
    public static function findAlgorithmFromHash(string $hash): ?HashingAlgorithmInterface;

    public static function findAlgorithmFromArray(array $data): ?HashingAlgorithmInterface;
}