<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Periodic;

use Gos\Bundle\WebSocketBundle\Periodic\PdoPeriodicPing;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class PdoPeriodicPingTest extends TestCase
{
    /**
     * @requires extension pdo
     */
    public function testTheDatabaseIsPinged()
    {
        $connection = $this->createMock(\PDO::class);
        $connection->expects($this->once())
            ->method('getAttribute')
            ->with(\PDO::ATTR_PERSISTENT)
            ->willReturn(null);

        $connection->expects($this->once())
            ->method('query')
            ->with('SELECT 1');

        (new PdoPeriodicPing($connection))->tick();
    }

    /**
     * @requires extension pdo
     */
    public function testTheDatabaseIsNotPingedForAPersistentConnection()
    {
        $connection = $this->createMock(\PDO::class);
        $connection->expects($this->once())
            ->method('getAttribute')
            ->with(\PDO::ATTR_PERSISTENT)
            ->willReturn(true);

        $connection->expects($this->never())
            ->method('query');

        (new PdoPeriodicPing($connection))->tick();
    }

    /**
     * @requires extension pdo
     */
    public function testAConnectionErrorIsLogged()
    {
        $logHandler = new TestHandler();

        $logger = new Logger(
            'websocket',
            [
                $logHandler
            ]
        );

        $connection = $this->createMock(\PDO::class);
        $connection->expects($this->once())
            ->method('getAttribute')
            ->with(\PDO::ATTR_PERSISTENT)
            ->willReturn(null);

        $connection->expects($this->once())
            ->method('query')
            ->willThrowException(new \PDOException('Testing'));

        $ping = new PdoPeriodicPing($connection);
        $ping->setLogger($logger);

        try {
            $ping->tick();

            $this->fail(sprintf('A %s should have been thrown.', \PDOException::class));
        } catch (\PDOException $exception) {
            $this->assertSame('Testing', $exception->getMessage());

            $this->assertTrue($logHandler->hasEmergencyThatContains('SQL server is gone, and unable to reconnect'));
        }
    }
}
