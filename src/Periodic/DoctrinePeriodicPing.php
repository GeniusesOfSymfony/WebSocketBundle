<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class DoctrinePeriodicPing implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Connection $connection;
    private int $interval = 20;

    public function __construct(Connection $connection, int $interval = 20)
    {
        $this->connection = $connection;
        $this->interval = $interval;
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
