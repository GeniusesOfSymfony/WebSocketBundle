<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Periodic;

use Gos\Bundle\WebSocketBundle\Periodic\PdoPeriodicPing;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

final class PdoPeriodicPingTest extends TestCase
{
    /**
     * @requires extension pdo
     */
    public function testTheDatabaseIsPinged(): void
    {
        /** @var MockObject&\PDO $connection */
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
    public function testTheDatabaseIsNotPingedForAPersistentConnection(): void
    {
        /** @var MockObject&\PDO $connection */
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
    public function testAConnectionErrorIsLogged(): void
    {
        $logger = new TestLogger();

        /** @var MockObject&\PDO $connection */
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

            $this->assertTrue($logger->hasEmergencyThatContains('SQL server is gone, and unable to reconnect'));
        }
    }
}
