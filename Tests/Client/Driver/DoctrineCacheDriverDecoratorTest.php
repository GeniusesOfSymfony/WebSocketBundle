<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Client\Driver;

use Doctrine\Common\Cache\Cache;
use Gos\Bundle\WebSocketBundle\Client\Driver\DoctrineCacheDriverDecorator;
use PHPUnit\Framework\TestCase;

class DoctrineCacheDriverDecoratorTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Cache
     */
    private $cache;

    /**
     * @var DoctrineCacheDriverDecorator
     */
    private $driver;

    protected function setUp()
    {
        parent::setUp();

        $this->cache = $this->createMock(Cache::class);

        $this->driver = new DoctrineCacheDriverDecorator($this->cache);
    }

    public function testDataIsRetrievedFromStorage()
    {
        $this->cache->expects($this->at(0))
            ->method('fetch')
            ->with('abc')
            ->willReturn('foo');

        $this->cache->expects($this->at(1))
            ->method('fetch')
            ->with('def')
            ->willReturn(false);

        $this->assertSame('foo', $this->driver->fetch('abc'));
        $this->assertFalse($this->driver->fetch('def'));
    }

    public function testStorageContainsData()
    {
        $this->cache->expects($this->at(0))
            ->method('contains')
            ->with('abc')
            ->willReturn(true);

        $this->cache->expects($this->at(1))
            ->method('contains')
            ->with('def')
            ->willReturn(false);

        $this->assertTrue($this->driver->contains('abc'));
        $this->assertFalse($this->driver->contains('def'));
    }

    public function testDataIsSavedInStorage()
    {
        $this->cache->expects($this->at(0))
            ->method('save')
            ->with('abc')
            ->willReturn(true);

        $this->cache->expects($this->at(1))
            ->method('save')
            ->with('def')
            ->willReturn(true);

        $this->assertTrue($this->driver->save('abc', 'data', 0));
        $this->assertTrue($this->driver->save('def', 'data', 60));
    }

    public function testDataIsDeletedFromStorage()
    {
        $this->cache->expects($this->at(0))
            ->method('delete')
            ->with('abc')
            ->willReturn(true);

        $this->assertTrue($this->driver->delete('abc'));
    }
}
