<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client\Driver;

use Gos\Bundle\WebSocketBundle\Client\Driver\PredisDriver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;

class PredisDriverTest extends TestCase
{
    /**
     * @var MockObject|ClientInterface
     */
    private $predis;

    /**
     * @var PredisDriver
     */
    private $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->predis = $this->createMock(ClientInterface::class);

        $this->driver = new PredisDriver($this->predis);
    }

    public function testDataIsRetrievedFromStorage()
    {
        $this->predis->expects($this->at(0))
            ->method('__call')
            ->with('get', ['abc'])
            ->willReturn('foo');

        $this->predis->expects($this->at(1))
            ->method('__call')
            ->with('get', ['def'])
            ->willReturn(null);

        $this->assertSame('foo', $this->driver->fetch('abc'));
        $this->assertNull($this->driver->fetch('def'));
    }

    public function testStorageContainsData()
    {
        $this->predis->expects($this->at(0))
            ->method('__call')
            ->with('exists', ['abc'])
            ->willReturn(1);

        $this->predis->expects($this->at(1))
            ->method('__call')
            ->with('exists', ['def'])
            ->willReturn(0);

        $this->assertTrue($this->driver->contains('abc'));
        $this->assertFalse($this->driver->contains('def'));
    }

    public function testDataIsSavedInStorage()
    {
        $this->predis->expects($this->at(0))
            ->method('__call')
            ->with('set', ['abc', 'data'])
            ->willReturn(true);

        $this->predis->expects($this->at(1))
            ->method('__call')
            ->with('setex', ['def', 60, 'data'])
            ->willReturn(true);

        $this->assertTrue($this->driver->save('abc', 'data', 0));
        $this->assertTrue($this->driver->save('def', 'data', 60));
    }

    public function testDataIsDeletedFromStorage()
    {
        $this->predis->expects($this->at(0))
            ->method('__call')
            ->with('del', [['abc']])
            ->willReturn(true);

        $this->assertTrue($this->driver->delete('abc'));
    }
}
