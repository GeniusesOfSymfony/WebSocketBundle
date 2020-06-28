<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PingableConnection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class DoctrinePeriodicPing implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Connection|PingableConnection
     */
    private $connection;

    /**
     * @var int
     */
    private $timeout = 20;

    /**
     * @param Connection|PingableConnection $connection
     *
     * @throws \InvalidArgumentException if the connection is not an appropriate type
     */
    public function __construct($connection)
    {
        if (!($connection instanceof Connection) && !($connection instanceof PingableConnection)) {
            throw new \InvalidArgumentException(sprintf('The connection must be a subclass of %s or implement %s, %s does not fulfill these requirements.', Connection::class, PingableConnection::class, \get_class($connection)));
        }

        $this->connection = $connection;
    }

    public function tick(): void
    {
        try {
            $startTime = microtime(true);

            if ($this->connection instanceof PingableConnection) {
                $this->connection->ping();
            } else {
                $this->connection->query($this->connection->getDatabasePlatform()->getDummySelectSQL());
            }

            $endTime = microtime(true);

            if (null !== $this->logger) {
                $this->logger->info(
                    sprintf('Successfully pinged database server (~%s ms)', round(($endTime - $startTime) * 100000, 2))
                );
            }
        } catch (DBALException $e) {
            if (null !== $this->logger) {
                $this->logger->emergency(
                    'Could not ping database server',
                    [
                        'exception' => $e,
                    ]
                );
            }

            throw $e;
        }
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }
}
