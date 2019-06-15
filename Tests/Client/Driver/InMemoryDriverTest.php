<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client\Driver;

use Gos\Bundle\WebSocketBundle\Client\Driver\InMemoryDriver;
use PHPUnit\Framework\TestCase;

class InMemoryDriverTest extends TestCase
{
    public function testDataIsProcessedInStorage()
    {
        $driver = new InMemoryDriver();

        $this->assertFalse($driver->contains('abc'));
        $this->assertTrue($driver->save('abc', 'data'));
        $this->assertTrue($driver->contains('abc'));
        $this->assertSame('data', $driver->fetch('abc'));
        $this->assertTrue($driver->delete('abc'));
        $this->assertFalse($driver->fetch('abc'));
    }
}
