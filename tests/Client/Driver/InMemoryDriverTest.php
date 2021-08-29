<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client\Driver;

use Gos\Bundle\WebSocketBundle\Client\Driver\InMemoryDriver;
use PHPUnit\Framework\TestCase;

/**
 * @group legacy
 */
class InMemoryDriverTest extends TestCase
{
    public function testDataIsProcessedInStorage(): void
    {
        $driver = new InMemoryDriver();

        self::assertFalse($driver->contains('abc'));
        self::assertTrue($driver->save('abc', 'data'));
        self::assertTrue($driver->contains('abc'));
        self::assertSame('data', $driver->fetch('abc'));
        self::assertTrue($driver->delete('abc'));
        self::assertFalse($driver->fetch('abc'));
        self::assertTrue($driver->save('abc', 'data'));
        self::assertTrue($driver->contains('abc'));
        $driver->clear();
        self::assertFalse($driver->contains('abc'));
    }
}
