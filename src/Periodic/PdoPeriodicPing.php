<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class PdoPeriodicPing implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private \PDO $pdo,
        private int $interval = 20,
    ) {
    }

    public function tick(): void
    {
        // If connection is persistent we don't need to ping
        if (true === $this->pdo->getAttribute(\PDO::ATTR_PERSISTENT)) {
            return;
        }

        try {
            $startTime = microtime(true);
            $this->pdo->query('SELECT 1');
            $endTime = microtime(true);

            $this->logger?->notice(
                sprintf('Successfully pinged SQL server (~%s ms)', round(($endTime - $startTime) * 100000, 2))
            );
        } catch (\PDOException $e) {
            $this->logger?->emergency(
                'SQL server is gone, and unable to reconnect',
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
