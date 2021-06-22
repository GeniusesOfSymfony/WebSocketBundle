<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class PdoPeriodicPing implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private \PDO $pdo;
    private int $interval;

    public function __construct(\PDO $pdo, int $interval = 20)
    {
        $this->pdo = $pdo;
        $this->interval = $interval;
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

            if (null !== $this->logger) {
                $this->logger->notice(
                    sprintf('Successfully pinged SQL server (~%s ms)', round(($endTime - $startTime) * 100000, 2))
                );
            }
        } catch (\PDOException $e) {
            if (null !== $this->logger) {
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

    public function getInterval(): int
    {
        return $this->interval;
    }

    /**
     * @deprecated to be removed in 4.0, use getInterval() instead
     */
    public function getTimeout(): int
    {
        trigger_deprecation('gos/web-socket-bundle', '3.9', '%s() is deprecated and will be removed in 4.0, call %s::getInterval() instead.', __METHOD__, self::class);

        return $this->getInterval();
    }

    public function setTimeout(int $timeout): void
    {
        trigger_deprecation('gos/web-socket-bundle', '3.9', '%s() is deprecated and will be removed in 4.0, set the timeout through the constructor instead.', __METHOD__);

        $this->interval = $timeout;
    }
}
