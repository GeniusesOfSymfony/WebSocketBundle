<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Periodic;

/**
 * @method int getInterval()
 */
interface PeriodicInterface
{
    public function tick(): void;

    /**
     * @deprecated to be removed in 4.0, use getInterval() instead
     */
    public function getTimeout(): int;
}
