<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use RandomPHP\Hashing\Hash;
use RandomPHP\Hashing\Algorithm\Argon2IDHashingAlgorithm;
use RandomPHP\Hashing\Algorithm\Argon2IHashingAlgorithm;

final class Argon2HashingAlgorithmTest extends TestCase
{
    private function hasArgon2id(): bool
    {
        return \defined('PASSWORD_ARGON2ID') && \in_array('argon2id', \password_algos(), true);
    }

    private function hasArgon2i(): bool
    {
        return \defined('PASSWORD_ARGON2I') && \in_array('argon2i', \password_algos(), true);
    }

    public function testArgon2idRoundTripAndDecode(): void
    {
        if (!$this->hasArgon2id()) {
            $this->markTestSkipped('Argon2id is not available in this PHP build.');
        }

        $algo = Argon2IDHashingAlgorithm::make(memory: 65536, iterations: 3, parallelism: 1);
        $hash = $algo->hash('pw');

        $this->assertTrue($hash->verify('pw'));
        $this->assertFalse($hash->verify('nope'));

        $decoded = Argon2IDHashingAlgorithm::decode($hash->toString());
        $this->assertNotNull($decoded);

        $arr = $decoded->toArray();
        $this->assertSame(Argon2IDHashingAlgorithm::class, $arr['type']);
        $this->assertSame(65536, $arr['params']['memory']);
        $this->assertSame(3, $arr['params']['iterations']);
        $this->assertSame(1, $arr['params']['parallelism']);

        // Hash::make should identify Argon2id as well
        $parsed = Hash::make($hash->toString());
        $this->assertSame(Argon2IDHashingAlgorithm::class, $parsed->getAlgo()::class);
    }

    public function testArgon2iFromArrayRoundTrip(): void
    {
        if (!$this->hasArgon2i()) {
            $this->markTestSkipped('Argon2i is not available in this PHP build.');
        }

        $algo = Argon2IHashingAlgorithm::make(memory: 32768, iterations: 2, parallelism: 1);
        $arr = $algo->toArray();

        $rehydrated = Argon2IHashingAlgorithm::fromArray($arr);
        $this->assertNotNull($rehydrated);
        $this->assertSame($arr, $rehydrated->toArray());
    }
}
