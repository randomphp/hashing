<?php

namespace RandomPHP\Hashing\Algorithm;

use RandomPHP\Hashing\AlgoCache;
use RandomPHP\Hashing\Exception\HashingException;
use RandomPHP\Hashing\Hash;
use RandomPHP\Hashing\Interface\HashingAlgorithmInterface;
use RandomPHP\Hashing\Interface\HashInterface;

final class BcryptHashingAlgorithm implements HashingAlgorithmInterface
{
    private function __construct(
        private readonly int $cost,
        private readonly bool $fixedVersion = true,
    ) {
    }

    /**
     * @inheritDoc
     * @throws HashingException
     */
    public function hash(string $clearText): HashInterface
    {
        return Hash::make(\password_hash(
            $clearText,
            PASSWORD_BCRYPT,
            [
                'cost' => $this->cost,
            ]
        ));
    }

    /**
     * @inheritDoc
     */
    public function verify(string $clearText, HashInterface $hash): bool
    {
        return \password_verify($clearText, $hash);
    }

    /**
     * @inheritDoc
     */
    public function match(HashingAlgorithmInterface $algorithm): bool
    {
        return $algorithm instanceof self &&
            $algorithm->cost === $this->cost &&
            $algorithm->fixedVersion === $this->fixedVersion;
    }

    /**
     * @inheritDoc
     */
    public static function decode(
        string $hash
    ): ?self {
        if (preg_match('/^\$(2[aby])\$(\d{2})\$/', $hash, $matches)) {
            return AlgoCache::cache(new self(
                cost: intval($matches[2]),
                fixedVersion: $matches[1] !== '2a',
            ));
        }
        return null;
    }

    /**
     * @param int $cost
     * @return self
     */
    public static function make(
        int $cost,
    ): self {
        return AlgoCache::cache(new self($cost));
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type' => self::class,
            'params' => [
                'cost' => $this->cost,
                'fixedVersion' => $this->fixedVersion,
            ],
        ];
    }

    /**
     * @param array $data
     * @return HashingAlgorithmInterface|null
     * @throws HashingException
     */
    public static function fromArray(array $data): ?HashingAlgorithmInterface
    {
        if (($data['type'] ?? null) !== self::class) {
            return null;
        }
        return AlgoCache::cache(
            new self(
                cost: $data['params']['cost'] ?? throw new HashingException('invalid or missing cost value'),
                fixedVersion: $data['params']['fixedVersion'] ?? throw new HashingException('invalid or missing fixedVersion value'),
            )
        );
    }
}