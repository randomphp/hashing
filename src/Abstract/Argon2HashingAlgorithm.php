<?php

namespace RandomPHP\Hashing\Abstract;

use RandomPHP\Hashing\AlgoCache;
use RandomPHP\Hashing\Exception\HashingException;
use RandomPHP\Hashing\Hash;
use RandomPHP\Hashing\Interface\HashingAlgorithmInterface;
use RandomPHP\Hashing\Interface\HashInterface;

/**
 * @internal
 */
abstract class Argon2HashingAlgorithm implements HashingAlgorithmInterface
{
    //These are hardcoded because PHP does not allow us to set them.
    final public const int SALT_LENGTH = 16;
    final public const int KEY_LENGTH = 32;

    /**
     * @var int
     */
    final public readonly int $memory;

    /**
     * @var int
     */
    final public readonly int $iterations;

    /**
     * @var int
     */
    final public readonly int $parallelism;

    /**
     * @var int
     */
    final public readonly int $version;

    /**
     * @var int
     */
    final public readonly int $saltLength;

    /**
     * @var int
     */
    final public readonly int $keyLength;

    /**
     * @param int $memory
     * @param int $iterations
     * @param int $parallelism
     * @param int|null $version
     * @param int|null $saltLength
     * @param int|null $keyLength
     */
    final protected function __construct(
        int $memory,
        int $iterations,
        int $parallelism,
        ?int $version = null,
        ?int $saltLength = null,
        ?int $keyLength = null,
    ) {
        $this->memory = $memory;
        $this->iterations = $iterations;
        $this->parallelism = $parallelism;
        $this->version = $version ?? -1;
        $this->saltLength = $saltLength ?? self::SALT_LENGTH;
        $this->keyLength = $keyLength ?? self::KEY_LENGTH;
    }

    /**
     * @inheritDoc
     * @throws HashingException
     */
    public function hash(string $clearText): HashInterface
    {
        return Hash::make(\password_hash(
            $clearText,
            static::getAlgo(),
            $this->getOptions(),
        ));
    }

    /**
     * @inheritDoc
     */
    public function match(HashingAlgorithmInterface $algorithm): bool
    {
        return $algorithm instanceof static &&
            $algorithm->memory === $this->memory &&
            $algorithm->iterations === $this->iterations &&
            $algorithm->parallelism === $this->parallelism &&
            $algorithm->saltLength === $this->saltLength &&
            $algorithm->keyLength === $this->keyLength && (
                $algorithm->version === -1 ||
                $this->version === -1 ||
                $algorithm->version === $this->version
            );
    }

    /**
     * @inheritDoc
     */
    public function verify(string $clearText, HashInterface $hash): bool
    {
        return \password_verify($clearText, $hash);
    }

    /**
     * @return string
     */
    protected static function getAlgo(): string
    {
        return '';
    }

    /**
     * @param int $memory
     * @param int $iterations
     * @param int $parallelism
     * @return static
     */
    final public static function make(
        int $memory,
        int $iterations,
        int $parallelism,
    ): static {
        return AlgoCache::cache(new static(
            memory: $memory,
            iterations: $iterations,
            parallelism: $parallelism,
        ));
    }

    /**
     * @param string $hash
     * @return HashingAlgorithmInterface|null
     */
    final public static function decode(
        string $hash,
    ): ?HashingAlgorithmInterface {
        // Split hash
        $parts = \explode('$', $hash);
        if (\count($parts) !== 6 || $parts[1] !== static::getAlgo()) {
            return null;
        }

        // Parse version (not used for now, but could be)
        if (
            !\preg_match('/v=(\d+)/', $parts[2], $verMatch) ||
            !\is_numeric($verMatch[1])
        ) {
            return null;
        }
        $version = intval($verMatch[1]);

        $paramsStr = $parts[3];
        \preg_match_all('/([mtp])=(\d+)/', $paramsStr, $matches, PREG_SET_ORDER);
        $memory = null;
        $iterations = null;
        $parallelism = null;
        foreach ($matches as $match) {
            if (!\is_numeric($match[2])) {
                return null;
            }
            $value = \intval($match[2]);
            switch ($match[1]) {
                case 'm':
                    $memory = $value;
                    break;
                case 't':
                    $iterations = $value;
                    break;
                case 'p':
                    $parallelism = $value;
                    break;
                default:
                    return null;
            }
        }

        // Decode salt and hash
        $salt = \base64_decode($parts[4], true);
        $key  = \base64_decode($parts[5], true);

        if ($salt === false || $key === false) {
            return null;
        }

        $saltLength = \strlen($salt);
        $keyLength = \strlen($key);

        if (!\is_int($memory) || !\is_int($iterations) || !\is_int($parallelism)) {
            return null;
        }

        return AlgoCache::cache(new static(
            memory: $memory,
            iterations: $iterations,
            parallelism: $parallelism,
            version: $version,
            saltLength: $saltLength,
            keyLength: $keyLength,
        ));
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type' => static::class,
            'params' => [
                'memory' => $this->memory,
                'iterations' => $this->iterations,
                'parallelism' => $this->parallelism,
                'version' => $this->version,
                'saltLength' => $this->saltLength,
                'keyLength' => $this->keyLength,
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
        if (($data['type'] ?? null) !== static::class) {
            return null;
        }
        return AlgoCache::cache(new static(
            memory: $data['params']['memory'] ?? throw new HashingException('invalid or missing memory value'),
            iterations: $data['params']['iterations'] ?? throw new HashingException('invalid or missing iterations value'),
            parallelism: $data['params']['parallelism'] ?? throw new HashingException('invalid or missing parallelism value'),
            version: $data['params']['version'] ?? throw new HashingException('invalid or missing version value'),
            saltLength: $data['params']['saltLength'] ?? throw new HashingException('invalid or missing saltLength value'),
            keyLength: $data['params']['keyLength'] ?? throw new HashingException('invalid or missing keyLength value'),
        ));
    }

    /**
     * @return array
     */
    private function getOptions(): array
    {
        return [
            'memory_cost' => $this->memory,
            'time_cost' => $this->iterations,
            'threads' => $this->parallelism,
        ];
    }
}