<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client\Driver;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Gos\Bundle\WebSocketBundle\Client\Driver\DoctrineCacheDriverDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DoctrineCacheDriverDecoratorTest extends TestCase
{
    /**
     * @var MockObject|DoctrineCache
     */
    private $cache;

    /**
     * @var DoctrineCacheDriverDecorator
     */
    private $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(DoctrineCache::class);

        $this->driver = new DoctrineCacheDriverDecorator($this->cache);
    }

    public function testDataIsRetrievedFromStorage(): void
    {
        $this->cache->expects(self::exactly(2))
            ->method('fetch')
            ->withConsecutive(['abc'], ['def'])
            ->willReturnOnConsecutiveCalls('foo', false);

        self::assertSame('foo', $this->driver->fetch('abc'));
        self::assertFalse($this->driver->fetch('def'));
    }

    public function testStorageContainsData(): void
    {
        $this->cache->expects(self::exactly(2))
            ->method('contains')
            ->withConsecutive(['abc'], ['def'])
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertTrue($this->driver->contains('abc'));
        self::assertFalse($this->driver->contains('def'));
    }

    public function testDataIsSavedInStorage(): void
    {
        $this->cache->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive(['abc'], ['def'])
            ->willReturnOnConsecutiveCalls(true, true);

        self::assertTrue($this->driver->save('abc', 'data', 0));
        self::assertTrue($this->driver->save('def', 'data', 60));
    }

    public function testDataIsDeletedFromStorage(): void
    {
        $this->cache->expects(self::once())
            ->method('delete')
            ->with('abc')
            ->willReturn(true);

        self::assertTrue($this->driver->delete('abc'));
    }

    public function testAllDataIsDeletedFromStorage(): void
    {
        $this->cache->expects(self::once())
            ->method('deleteAll')
            ->willReturn(true);

        $this->driver->clear();
    }
}

interface DoctrineCache extends Cache, ClearableCache
{
}
