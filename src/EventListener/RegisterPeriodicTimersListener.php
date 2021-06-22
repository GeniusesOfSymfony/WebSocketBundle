<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class RegisterPeriodicTimersListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private PeriodicRegistry $periodicRegistry;

    public function __construct(PeriodicRegistry $periodicRegistry)
    {
        $this->periodicRegistry = $periodicRegistry;
    }

    public function registerPeriodics(ServerLaunchedEvent $event): void
    {
        $loop = $event->getEventLoop();

        foreach ($this->periodicRegistry->getPeriodics() as $periodic) {
            $interval = method_exists($periodic, 'getInterval') ? $periodic->getInterval() : $periodic->getTimeout();

            $loop->addPeriodicTimer($interval, [$periodic, 'tick']);

            if (null !== $this->logger) {
                $this->logger->info(
                    sprintf(
                        'Registered periodic callback %s, executed every %d seconds',
                        \get_class($periodic),
                        $interval
                    )
                );
            }
        }
    }
}
