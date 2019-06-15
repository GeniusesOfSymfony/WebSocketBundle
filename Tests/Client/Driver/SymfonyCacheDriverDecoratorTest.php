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

    public function testDataIsRetrievedFromStorage()
    {
        $hitCacheItem = $this->createMock(ItemInterface::class);
        $hitCacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $hitCacheItem->expects($this->once())
            ->method('get')
            ->willReturn('foo');

        $this->cache->expects($this->at(0))
            ->method('getItem')
            ->with('abc')
            ->willReturn($hitCacheItem);

        $missedCacheItem = $this->createMock(ItemInterface::class);
        $missedCacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $missedCacheItem->expects($this->never())
            ->method('get');

        $this->cache->expects($this->at(1))
            ->method('getItem')
            ->with('def')
            ->willReturn($missedCacheItem);

        $this->assertSame('foo', $this->driver->fetch('abc'));
        $this->assertFalse($this->driver->fetch('def'));
    }

    public function testStorageContainsData()
    {
        $this->cache->expects($this->at(0))
            ->method('hasItem')
            ->with('abc')
            ->willReturn(true);

        $this->cache->expects($this->at(1))
            ->method('hasItem')
            ->with('def')
            ->willReturn(false);

        $this->assertTrue($this->driver->contains('abc'));
        $this->assertFalse($this->driver->contains('def'));
    }

    public function testDataIsSavedInStorage()
    {
        $noLifetimeCacheItem = $this->createMock(ItemInterface::class);
        $noLifetimeCacheItem->expects($this->once())
            ->method('set')
            ->with('data');

        $noLifetimeCacheItem->expects($this->never())
            ->method('expiresAfter');

        $this->cache->expects($this->at(0))
            ->method('getItem')
            ->with('abc')
            ->willReturn($noLifetimeCacheItem);

        $this->cache->expects($this->at(1))
            ->method('save')
            ->with($noLifetimeCacheItem)
            ->willReturn(true);

        $lifetimeCacheItem = $this->createMock(ItemInterface::class);
        $lifetimeCacheItem->expects($this->once())
            ->method('set')
            ->with('data');

        $lifetimeCacheItem->expects($this->once())
            ->method('expiresAfter')
            ->with(60);

        $this->cache->expects($this->at(2))
            ->method('getItem')
            ->with('def')
            ->willReturn($lifetimeCacheItem);

        $this->cache->expects($this->at(3))
            ->method('save')
            ->with($noLifetimeCacheItem)
            ->willReturn(true);

        $this->assertTrue($this->driver->save('abc', 'data', 0));
        $this->assertTrue($this->driver->save('def', 'data', 60));
    }

    public function testDataIsDeletedFromStorage()
    {
        $this->cache->expects($this->at(0))
            ->method('deleteItem')
            ->with('abc')
            ->willReturn(true);

        $this->assertTrue($this->driver->delete('abc'));
    }
}
