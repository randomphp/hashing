<?php

namespace RandomPHP\Hashing;

use RandomPHP\Hashing\Algorithm\Argon2IDHashingAlgorithm;
use RandomPHP\Hashing\Algorithm\Argon2IHashingAlgorithm;
use RandomPHP\Hashing\Algorithm\BcryptHashingAlgorithm;
use RandomPHP\Hashing\Exception\HashingException;
use RandomPHP\Hashing\Interface\HashingAlgorithmInterface;
use RandomPHP\Hashing\Interface\HashInterface;

final class Hash implements HashInterface
{
    /**
     * @var \class-string<HashingAlgorithmInterface>[]
     */
    private static array $knownAlgorithms = [
        Argon2IDHashingAlgorithm::class,
        Argon2IHashingAlgorithm::class,
        BcryptHashingAlgorithm::class,
    ];

    /**
     * @param string $hash
     * @param HashingAlgorithmInterface $algorithm
     */
    private function __construct(
        private readonly string $hash,
        private readonly HashingAlgorithmInterface $algorithm,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getAlgo(): HashingAlgorithmInterface
    {
        return $this->algorithm;
    }

    /**
     * @inheritDoc
     */
    public function needsRehash(HashingAlgorithmInterface $algorithm): bool
    {
        return !$this->getAlgo()->match($algorithm);
    }

    /**
     * @inheritDoc
     */
    public function verify(string $clearTest): bool
    {
        return $this->getAlgo()->verify($clearTest, $this);
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return $this->hash;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * @param string $hash
     * @return HashingAlgorithmInterface|null
     */
    public static function findAlgorithmFromHash(string $hash): ?HashingAlgorithmInterface
    {
        foreach (self::$knownAlgorithms as $algorithm) {
            $algo = $algorithm::decode($hash);
            if ($algo !== null) {
                return $algo;
            }
        }
        return null;
    }

    /**
     * @param array $data
     * @return HashingAlgorithmInterface|null
     */
    public static function findAlgorithmFromArray(array $data): ?HashingAlgorithmInterface
    {
        foreach (self::$knownAlgorithms as $algorithm) {
            $algo = $algorithm::fromArray($data);
            if ($algo !== null) {
                return $algo;
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     * @throws HashingException
     */
    public static function make(string $hash): HashInterface
    {
        $algo = self::findAlgorithmFromHash($hash);
        if ($algo === null) {
            throw new HashingException('failed to identify hashing algorithm from hash string');
        }
        return new self(
            hash: $hash,
            algorithm: $algo,
        );
    }

    /**
     * @param string $algorithmClass
     * @return void
     * @throws HashingException
     */
    public static function registerAlgorithm(string $algorithmClass): void
    {
        if (!\class_exists($algorithmClass)) {
            throw new HashingException(\sprintf(
                'invalid or unknown hashing algorithm: "%s"',
                $algorithmClass
            ));
        }

        if (!\is_subclass_of($algorithmClass, HashingAlgorithmInterface::class)) {
            throw new HashingException(\sprintf(
                'invalid hashing algorithm: "%s" is not an instance of %s',
                $algorithmClass,
                HashingAlgorithmInterface::class
            ));
        }

        self::$knownAlgorithms[] = $algorithmClass;
    }
}