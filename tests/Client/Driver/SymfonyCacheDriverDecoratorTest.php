<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client\Driver;

use Gos\Bundle\WebSocketBundle\Client\Driver\SymfonyCacheDriverDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SymfonyCacheDriverDecoratorTest extends TestCase
{
    /**
     * @var MockObject|AdapterInterface
     */
    private $cache;

    /**
     * @var SymfonyCacheDriverDecorator
     */
    private $driver;

    protected function setUp(): void
    {
        if (!interface_exists(ItemInterface::class)) {
            $this->markTestSkipped('Test is skipped with symfony/cache <= 4.1');
        }

        parent::setUp();

        $this->cache = $this->createMock(AdapterInterface::class);

        $this->driver = new SymfonyCacheDriverDecorator($this->cache);
    }

    public function testDataIsRetrievedFromStorage(): void
    {
        $hitCacheItem = $this->createMock(ItemInterface::class);
        $hitCacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $hitCacheItem->expects($this->once())
            ->method('get')
            ->willReturn('foo');

        $missedCacheItem = $this->createMock(ItemInterface::class);
        $missedCacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $missedCacheItem->expects($this->never())
            ->method('get');

        $this->cache->expects($this->exactly(2))
            ->method('getItem')
            ->withConsecutive(['abc'], ['def'])
            ->willReturnOnConsecutiveCalls($hitCacheItem, $missedCacheItem);

        $this->assertSame('foo', $this->driver->fetch('abc'));
        $this->assertFalse($this->driver->fetch('def'));
    }

    public function testStorageContainsData(): void
    {
        $this->cache->expects($this->exactly(2))
            ->method('hasItem')
            ->withConsecutive(['abc'], ['def'])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($this->driver->contains('abc'));
        $this->assertFalse($this->driver->contains('def'));
    }

    public function testDataIsSavedInStorage(): void
    {
        $noLifetimeCacheItem = $this->createMock(ItemInterface::class);
        $noLifetimeCacheItem->expects($this->once())
            ->method('set')
            ->with('data');

        $noLifetimeCacheItem->expects($this->never())
            ->method('expiresAfter');

        $lifetimeCacheItem = $this->createMock(ItemInterface::class);
        $lifetimeCacheItem->expects($this->once())
            ->method('set')
            ->with('data');

        $lifetimeCacheItem->expects($this->once())
            ->method('expiresAfter')
            ->with(60);

        $this->cache->expects($this->exactly(2))
            ->method('getItem')
            ->withConsecutive(['abc'], ['def'])
            ->willReturnOnConsecutiveCalls($noLifetimeCacheItem, $lifetimeCacheItem);

        $this->cache->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive([$noLifetimeCacheItem], [$lifetimeCacheItem])
            ->willReturnOnConsecutiveCalls(true, true);

        $this->assertTrue($this->driver->save('abc', 'data', 0));
        $this->assertTrue($this->driver->save('def', 'data', 60));
    }

    public function testDataIsDeletedFromStorage(): void
    {
        $this->cache->expects($this->once())
            ->method('deleteItem')
            ->with('abc')
            ->willReturn(true);

        $this->assertTrue($this->driver->delete('abc'));
    }

    public function testAllDataIsDeletedFromStorage(): void
    {
        $this->cache->expects($this->once())
            ->method('clear')
            ->willReturn(true);

        $this->driver->clear();
    }
}
