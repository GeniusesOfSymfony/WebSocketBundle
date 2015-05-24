<?php

namespace Gos\Bundle\WebSocketBundle\Periodic;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PeriodicMemoryUsage implements PeriodicInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = null === $logger ? new NullLogger() : $logger;
    }

    /**
     * Function excecuted n timeout.
     */
    public function tick()
    {
        $this->logger->debug('Memory usage : ' . round((memory_get_usage() / (1024 * 1024)), 2) . 'Mo');
    }

    /**
     * @return int (in second)
     */
    public function getTimeout()
    {
        return 5;
    }
}
