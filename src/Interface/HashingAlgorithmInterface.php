<?php

namespace RandomPHP\Hashing\Interface;

interface HashingAlgorithmInterface
{
    /**
     * @param string $clearText
     * @return HashInterface
     */
    public function hash(string $clearText): HashInterface;

    /**
     * @param string $clearText
     * @param HashInterface $hash
     * @return bool
     */
    public function verify(string $clearText, HashInterface $hash): bool;

    /**
     * @param HashingAlgorithmInterface $algorithm
     * @return bool
     */
    public function match(HashingAlgorithmInterface $algorithm): bool;

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @param string $hash
     * @return HashInterface|null
     */
    public static function decode(string $hash): ?HashingAlgorithmInterface;

    /**
     * @param array $data
     * @return HashingAlgorithmInterface|null
     */
    public static function fromArray(array $data): ?HashingAlgorithmInterface;
}