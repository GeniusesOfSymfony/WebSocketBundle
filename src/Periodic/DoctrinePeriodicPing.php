<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class DoctrinePeriodicPing implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private Connection $connection,
        private int $interval = 20,
    ) {
    }

    public function tick(): void
    {
        try {
            $startTime = microtime(true);

            $this->connection->executeQuery($this->connection->getDatabasePlatform()->getDummySelectSQL());

            $endTime = microtime(true);

            $this->logger?->info(
                sprintf('Successfully pinged database server (~%s ms)', round(($endTime - $startTime) * 100000, 2))
            );
        } catch (DBALException $e) {
            $this->logger?->emergency(
                'Could not ping database server',
                [
                    'exception' => $e,
                ]
            );

            throw $e;
        }
    }

    public function getInterval(): int
    {
        return $this->interval;
    }
}
