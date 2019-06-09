<?php

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class PdoPeriodicPing implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var int
     */
    private $timeout = 20;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
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

            if ($this->logger) {
                $this->logger->notice(
                    sprintf('Successfully pinged SQL server (~%s ms)', round(($endTime - $startTime) * 100000), 2)
                );
            }
        } catch (\PDOException $e) {
            if ($this->logger) {
                $this->logger->emergency(
                    'SQL server is gone, and unable to reconnect',
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
