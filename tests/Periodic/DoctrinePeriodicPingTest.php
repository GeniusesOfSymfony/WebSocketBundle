<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Periodic;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException as LegacyDBALException;
use Doctrine\DBAL\Driver\PingableConnection;
use Doctrine\DBAL\Exception as NewDBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Gos\Bundle\WebSocketBundle\Periodic\DoctrinePeriodicPing;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class DoctrinePeriodicPingTest extends TestCase
{
    public function testTheDatabaseIsPingedWithAConnection(): void
    {
        $query = 'SELECT 1';

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects(self::once())
            ->method('getDummySelectSQL')
            ->willReturn($query);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $connection->expects(self::once())
            ->method('executeQuery')
            ->with($query);

        $ping = new DoctrinePeriodicPing($connection);
        $ping->tick();
    }

    /**
     * @group legacy
     */
    public function testTheTimeoutCanBeAdjustedAtRuntime(): void
    {
        $connection = $this->createMock(Connection::class);

        $ping = new DoctrinePeriodicPing($connection);
        $ping->setTimeout(15);

        self::assertSame(15, $ping->getTimeout());
    }

    /**
     * @group legacy
     */
    public function testTheDatabaseIsPingedWithAPingableConnection(): void
    {
        if (!interface_exists(PingableConnection::class)) {
            self::markTestSkipped('Test applies to doctrine/dbal 2.x');
        }

        $connection = $this->createMock(PingableConnection::class);
        $connection->expects(self::once())
            ->method('ping');

        $ping = new DoctrinePeriodicPing($connection);
        $ping->tick();
    }

    public function testAValidObjectIsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The connection must be a subclass of Doctrine\DBAL\Connection or implement Doctrine\DBAL\Driver\PingableConnection, stdClass does not fulfill these requirements.');

        new DoctrinePeriodicPing(new \stdClass());
    }

    /**
     * @group legacy
     */
    public function testAConnectionErrorIsLogged(): void
    {
        $logger = new NullLogger();

        $exceptionClass = class_exists(NewDBALException::class) ? NewDBALException::class : LegacyDBALException::class;

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willThrowException(new $exceptionClass('Testing'));

        $ping = new DoctrinePeriodicPing($connection);
        $ping->setLogger($logger);

        try {
            $ping->tick();

            self::fail(sprintf('A %s should have been thrown.', $exceptionClass));
        } catch (LegacyDBALException | NewDBALException $exception) {
            self::assertSame('Testing', $exception->getMessage());
        }
    }
}
