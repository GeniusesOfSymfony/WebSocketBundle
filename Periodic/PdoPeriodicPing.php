<?php

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PdoPeriodicPing implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var int|float
     */
    protected $timeout = 20;

    /**
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param int|float $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function tick()
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

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
}
