<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class PeriodicMemoryUsage implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function tick(): void
    {
        if (null !== $this->logger) {
            $this->logger->info('Memory usage : '.round((memory_get_usage() / (1024 * 1024)), 4).'Mo');
        }
    }

    public function getInterval(): int
    {
        return 5;
    }

    /**
     * @deprecated to be removed in 4.0, use getInterval() instead
     */
    public function getTimeout(): int
    {
        trigger_deprecation('gos/web-socket-bundle', '3.9', '%s() is deprecated and will be removed in 4.0, call %s::getInterval() instead.', __METHOD__, self::class);

        return $this->getInterval();
    }
}
