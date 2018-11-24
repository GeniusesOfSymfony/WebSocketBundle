<?php

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class PeriodicMemoryUsage implements PeriodicInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Function excecuted n timeout.
     */
    public function tick()
    {
        if ($this->logger) {
            $this->logger->info('Memory usage : ' . round((memory_get_usage() / (1024 * 1024)), 4) . 'Mo');
        }
    }

    /**
     * @return int (in second)
     */
    public function getTimeout()
    {
        return 5;
    }
}
