<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use RandomPHP\Hashing\Hash;
use RandomPHP\Hashing\Exception\HashingException;
use RandomPHP\Hashing\Algorithm\BcryptHashingAlgorithm;
use RandomPHP\Hashing\Interface\HashingAlgorithmInterface;

final class HashTest extends TestCase
{
    public function testJsonSerializeAndToString(): void
    {
        $algo = BcryptHashingAlgorithm::make(10);
        $hash = $algo->hash('pw');

        $this->assertSame($hash->toString(), (string) $hash);
        $this->assertSame('"' . $hash->toString() . '"', json_encode($hash, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    public function testMakeThrowsForUnknownHashFormat(): void
    {
        $this->expectException(HashingException::class);
        Hash::make('not-a-real-hash-format');
    }

    public function testNeedsRehashUsesAlgorithmMatch(): void
    {
        $algo10 = BcryptHashingAlgorithm::make(10);
        $algo11 = BcryptHashingAlgorithm::make(11);

        $hash = $algo10->hash('pw');

        $this->assertFalse($hash->needsRehash($algo10));
        $this->assertTrue($hash->needsRehash($algo11));
    }

    public function testRegisterAlgorithmValidatesClassExists(): void
    {
        $this->expectException(HashingException::class);
        Hash::registerAlgorithm('\\This\\Class\\Does\\Not\\Exist');
    }

    public function testRegisterAlgorithmValidatesInterface(): void
    {
        $this->expectException(HashingException::class);
        Hash::registerAlgorithm(\stdClass::class);
    }

    public function testFindAlgorithmFromArrayViaKnownAlgorithms(): void
    {
        $algo = BcryptHashingAlgorithm::make(10);
        $data = $algo->toArray();

        $found = Hash::findAlgorithmFromArray($data);
        $this->assertInstanceOf(HashingAlgorithmInterface::class, $found);
        $this->assertSame($data, $found->toArray());
    }
}
