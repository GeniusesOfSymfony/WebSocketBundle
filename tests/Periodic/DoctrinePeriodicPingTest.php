<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Periodic;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Gos\Bundle\WebSocketBundle\Periodic\DoctrinePeriodicPing;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class DoctrinePeriodicPingTest extends TestCase
{
    public function testTheDatabaseIsPingedWithAConnection(): void
    {
        $logger = new TestLogger();

        $query = 'SELECT 1';

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects($this->once())
            ->method('getDummySelectSQL')
            ->willReturn($query);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $connection->expects($this->once())
            ->method('executeQuery')
            ->with($query);

        $ping = new DoctrinePeriodicPing($connection);
        $ping->setLogger($logger);
        $ping->tick();

        $this->assertTrue($logger->hasInfoThatContains('Successfully pinged database server '));
    }

    public function testAConnectionErrorIsLogged(): void
    {
        $logger = new TestLogger();

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
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
