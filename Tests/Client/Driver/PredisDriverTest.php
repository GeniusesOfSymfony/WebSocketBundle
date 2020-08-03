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

    public function testDataIsRetrievedFromStorage(): void
    {
        $this->predis->expects($this->exactly(2))
            ->method('__call')
            ->withConsecutive(['get', ['abc']], ['get', ['def']])
            ->willReturnOnConsecutiveCalls('foo', null);

        $this->assertSame('foo', $this->driver->fetch('abc'));
        $this->assertNull($this->driver->fetch('def'));
    }

    public function testStorageContainsData(): void
    {
        $this->predis->expects($this->exactly(2))
            ->method('__call')
            ->withConsecutive(['exists', ['abc']], ['exists', ['def']])
            ->willReturnOnConsecutiveCalls(1, 0);

        $this->assertTrue($this->driver->contains('abc'));
        $this->assertFalse($this->driver->contains('def'));
    }

    public function testDataIsSavedInStorage(): void
    {
        $this->predis->expects($this->exactly(2))
            ->method('__call')
            ->withConsecutive(['set', ['abc', 'data']], ['setex', ['def', 60, 'data']])
            ->willReturnOnConsecutiveCalls(true, true);

        $this->assertTrue($this->driver->save('abc', 'data', 0));
        $this->assertTrue($this->driver->save('def', 'data', 60));
    }

    public function testDataIsDeletedFromStorage(): void
    {
        $this->predis->expects($this->once())
            ->method('__call')
            ->with('del', [['abc']])
            ->willReturn(true);

        $this->assertTrue($this->driver->delete('abc'));
    }
}
