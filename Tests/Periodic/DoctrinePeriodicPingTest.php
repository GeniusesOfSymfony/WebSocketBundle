<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Periodic;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Gos\Bundle\WebSocketBundle\Periodic\DoctrinePeriodicPing;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class DoctrinePeriodicPingTest extends TestCase
{
    public function testTheDatabaseIsPinged()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('ping');

        (new DoctrinePeriodicPing($connection))->tick();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The connection must be a subclass of Doctrine\DBAL\Connection or implement Doctrine\DBAL\Driver\PingableConnection, stdClass does not fulfill these requirements.
     */
    public function testAValidObjectIsRequired()
    {
        new DoctrinePeriodicPing(new \stdClass());
    }

    public function testAConnectionErrorIsLogged()
    {
        $logHandler = new TestHandler();

        $logger = new Logger(
            'websocket',
            [
                $logHandler
            ]
        );

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('ping')
            ->willThrowException(new DBALException('Testing'));

        $ping = new DoctrinePeriodicPing($connection);
        $ping->setLogger($logger);

        try {
            $ping->tick();

            $this->fail(sprintf('A %s should have been thrown.', DBALException::class));
        } catch (DBALException $exception) {
            $this->assertSame('Testing', $exception->getMessage());

            $this->assertTrue($logHandler->hasEmergencyThatContains('Could not ping database server'));
        }
    }
}
