<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Periodic\PeriodicMemoryUsage;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class RegisterPeriodicMemoryTimerListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var PeriodicRegistry
     */
    private $periodicRegistry;

    public function __construct(PeriodicRegistry $periodicRegistry)
    {
        $this->periodicRegistry = $periodicRegistry;
    }

    public function registerPeriodicHandler(ServerEvent $event): void
    {
        if (!$event->isProfiling()) {
            return;
        }

        $memoryUsagePeriodicTimer = new PeriodicMemoryUsage();

        if (null !== $this->logger) {
            $memoryUsagePeriodicTimer->setLogger($this->logger);
        }

        $this->periodicRegistry->addPeriodic($memoryUsagePeriodicTimer);
    }
}
