<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class DoctrinePeriodicPing implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Connection $connection;
    private int $timeout = 20;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function tick(): void
    {
        try {
            $startTime = microtime(true);

            $this->connection->query($this->connection->getDatabasePlatform()->getDummySelectSQL());

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
