<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use RandomPHP\Hashing\AlgoCache;
use RandomPHP\Hashing\Algorithm\BcryptHashingAlgorithm;

final class AlgoCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        // Ensure cache behavior is predictable across tests
        AlgoCache::toggle(true);
    }

    public function testToggleReturnsOldValueAndChangesState(): void
    {
        $old = AlgoCache::toggle(false);
        $this->assertIsBool($old);
        $this->assertFalse(AlgoCache::isEnabled());

        $old2 = AlgoCache::toggle(true);
        $this->assertFalse($old2);
        $this->assertTrue(AlgoCache::isEnabled());
    }

    public function testDisabledTemporarilyDisablesAndRestores(): void
    {
        AlgoCache::toggle(true);

        $result = AlgoCache::disabled(function (): bool {
            $this->assertFalse(AlgoCache::isEnabled(), 'Cache should be disabled inside disabled() callback');
            return true;
        });

        $this->assertTrue($result);
        $this->assertTrue(AlgoCache::isEnabled(), 'Cache state should be restored after disabled()');
    }

    public function testCacheReturnsSameInstanceWhenEnabled(): void
    {
        AlgoCache::toggle(true);

        $a = BcryptHashingAlgorithm::make(10);
        $b = BcryptHashingAlgorithm::make(10);

        $this->assertSame($a, $b, 'Same arguments should return same cached instance when enabled');
    }

    public function testCacheReturnsDifferentInstancesWhenDisabled(): void
    {
        AlgoCache::toggle(false);

        $a = BcryptHashingAlgorithm::make(10);
        $b = BcryptHashingAlgorithm::make(10);

        $this->assertNotSame($a, $b, 'Cache disabled should not reuse instances');
    }

    public function testCacheDifferentiatesByArguments(): void
    {
        AlgoCache::toggle(true);

        $a = BcryptHashingAlgorithm::make(10);
        $b = BcryptHashingAlgorithm::make(11);

        $this->assertNotSame($a, $b, 'Different params should not hit same cached instance');
    }
}
