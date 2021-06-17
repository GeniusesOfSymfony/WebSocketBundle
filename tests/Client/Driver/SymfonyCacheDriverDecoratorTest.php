<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client\Driver;

use Gos\Bundle\WebSocketBundle\Client\Driver\SymfonyCacheDriverDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @group legacy
 */
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
        parent::setUp();

        $this->cache = $this->createMock(AdapterInterface::class);

        $this->driver = new SymfonyCacheDriverDecorator($this->cache);
    }

    public function testDataIsRetrievedFromStorage(): void
    {
        $hitCacheItem = new CacheItem();
        $hitCacheItem->set('foo');

        $isHitProperty = (new \ReflectionClass($hitCacheItem))->getProperty('isHit');
        $isHitProperty->setAccessible(true);
        $isHitProperty->setValue($hitCacheItem, true);

        $missedCacheItem = new CacheItem();

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
        $noLifetimeCacheItem = new CacheItem();
        $lifetimeCacheItem = new CacheItem();

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

        $expiryProperty = (new \ReflectionClass(CacheItem::class))->getProperty('expiry');
        $expiryProperty->setAccessible(true);

        self::assertNull($expiryProperty->getValue($noLifetimeCacheItem));
        self::assertNotNull($expiryProperty->getValue($lifetimeCacheItem));
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
