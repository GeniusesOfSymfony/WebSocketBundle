<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Periodic;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Gos\Bundle\WebSocketBundle\Periodic\DoctrinePeriodicPing;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class DoctrinePeriodicPingTest extends TestCase
{
    public function testTheDatabaseIsPinged()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('ping');

        (new DoctrinePeriodicPing($connection))->tick();
    }

    public function testAValidObjectIsRequired()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The connection must be a subclass of Doctrine\DBAL\Connection or implement Doctrine\DBAL\Driver\PingableConnection, stdClass does not fulfill these requirements.');

        new DoctrinePeriodicPing(new \stdClass());
    }

    public function testAConnectionErrorIsLogged()
    {
        $logger = new TestLogger();

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

            $this->assertTrue($logger->hasEmergencyThatContains('Could not ping database server'));
        }
    }
}
