<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client\Driver;

use Gos\Bundle\WebSocketBundle\Client\Driver\SymfonyCacheDriverDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @group legacy
 */
final class SymfonyCacheDriverDecoratorTest extends TestCase
{
    /**
     * @var MockObject&AdapterInterface
     */
    private $cache;

    /**
     * @var SymfonyCacheDriverDecorator
     */
    private $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(AdapterInterface::class);

        $this->driver = new SymfonyCacheDriverDecorator($this->cache);
    }

    public function testDataIsRetrievedFromStorage(): void
    {
        $hitCacheItem = $this->createMock(ItemInterface::class);
        $hitCacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);

        $hitCacheItem->expects(self::once())
            ->method('get')
            ->willReturn('foo');

        $missedCacheItem = $this->createMock(ItemInterface::class);
        $missedCacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);

        $missedCacheItem->expects(self::never())
            ->method('get');

        $this->cache->expects(self::exactly(2))
            ->method('getItem')
            ->withConsecutive(['abc'], ['def'])
            ->willReturnOnConsecutiveCalls($hitCacheItem, $missedCacheItem);

        self::assertSame('foo', $this->driver->fetch('abc'));
        self::assertFalse($this->driver->fetch('def'));
    }

    public function testStorageContainsData(): void
    {
        $this->cache->expects(self::exactly(2))
            ->method('hasItem')
            ->withConsecutive(['abc'], ['def'])
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertTrue($this->driver->contains('abc'));
        self::assertFalse($this->driver->contains('def'));
    }

    public function testDataIsSavedInStorage(): void
    {
        $noLifetimeCacheItem = $this->createMock(ItemInterface::class);
        $noLifetimeCacheItem->expects(self::once())
            ->method('set')
            ->with('data');

        $noLifetimeCacheItem->expects(self::never())
            ->method('expiresAfter');

        $lifetimeCacheItem = $this->createMock(ItemInterface::class);
        $lifetimeCacheItem->expects(self::once())
            ->method('set')
            ->with('data');

        $lifetimeCacheItem->expects(self::once())
            ->method('expiresAfter')
            ->with(60);

        $this->cache->expects(self::exactly(2))
            ->method('getItem')
            ->withConsecutive(['abc'], ['def'])
            ->willReturnOnConsecutiveCalls($noLifetimeCacheItem, $lifetimeCacheItem);

        $this->cache->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive([$noLifetimeCacheItem], [$lifetimeCacheItem])
            ->willReturnOnConsecutiveCalls(true, true);

        self::assertTrue($this->driver->save('abc', 'data', 0));
        self::assertTrue($this->driver->save('def', 'data', 60));
    }

    public function testDataIsDeletedFromStorage(): void
    {
        $this->cache->expects(self::once())
            ->method('deleteItem')
            ->with('abc')
            ->willReturn(true);

        self::assertTrue($this->driver->delete('abc'));
    }

    public function testAllDataIsDeletedFromStorage(): void
    {
        $this->cache->expects(self::once())
            ->method('clear')
            ->willReturn(true);

        $this->driver->clear();
    }
}
