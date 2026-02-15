<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use RandomPHP\Hashing\Algorithm\BcryptHashingAlgorithm;
use RandomPHP\Hashing\Hash;
use RandomPHP\Hashing\Interface\HashInterface;

final class BcryptHashingAlgorithmTest extends TestCase
{
    public function testHashAndVerifyRoundTrip(): void
    {
        $algo = BcryptHashingAlgorithm::make(10);

        $hash = $algo->hash('correct horse battery staple');
        $this->assertInstanceOf(HashInterface::class, $hash);

        $this->assertTrue($hash->verify('correct horse battery staple'));
        $this->assertFalse($hash->verify('wrong password'));
    }

    public function testDecodeExtractsCostAndFixedVersionFromHash(): void
    {
        $algo = BcryptHashingAlgorithm::make(10);
        $hash = $algo->hash('pw');

        $decoded = BcryptHashingAlgorithm::decode($hash->toString());
        $this->assertNotNull($decoded);

        $arr = $decoded->toArray();
        $this->assertSame(BcryptHashingAlgorithm::class, $arr['type']);
        $this->assertSame(10, $arr['params']['cost']);

        // password_hash() on modern PHP typically outputs $2y$..., which implies fixedVersion = true
        $this->assertIsBool($arr['params']['fixedVersion']);
    }

    public function testFromArrayRoundTrip(): void
    {
        $algo = BcryptHashingAlgorithm::make(12);
        $arr = $algo->toArray();

        $rehydrated = BcryptHashingAlgorithm::fromArray($arr);
        $this->assertNotNull($rehydrated);

        $this->assertSame($arr, $rehydrated->toArray());
    }

    public function testHashMakeFindsBcryptAlgorithm(): void
    {
        $algo = BcryptHashingAlgorithm::make(10);
        $hash = $algo->hash('pw');

        $parsed = Hash::make($hash->toString());
        $this->assertSame($hash->toString(), $parsed->toString());

        $parsedAlgo = $parsed->getAlgo();
        $this->assertSame(BcryptHashingAlgorithm::class, $parsedAlgo::class);

        // Verify goes through algorithm selected by Hash::make()
        $this->assertTrue($parsed->verify('pw'));
        $this->assertFalse($parsed->verify('nope'));
    }
}
