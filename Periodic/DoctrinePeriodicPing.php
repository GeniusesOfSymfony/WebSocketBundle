<?php

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PingableConnection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class DoctrinePeriodicPing implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Connection|PingableConnection
     */
    protected $connection;

    /**
     * @var int|float
     */
    protected $timeout = 20;

    /**
     * @param Connection|PingableConnection $connection
     */
    public function __construct($connection)
    {
        if (!($connection instanceof Connection) && !($connection instanceof PingableConnection)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The connection must be a subclass of %s or implement %s, %s does not fulfill these requirements.',
                    Connection::class,
                    PingableConnection::class,
                    get_class($connection)
                )
            );
        }

        $this->connection = $connection;
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
        try {
            $startTime = microtime(true);
            $this->connection->ping();
            $endTime = microtime(true);

            if ($this->logger) {
                $this->logger->info(
                    sprintf('Successfully pinged database server (~%s ms)', round(($endTime - $startTime) * 100000), 2)
                );
            }
        } catch (DBALException $e) {
            if ($this->logger) {
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

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
}
