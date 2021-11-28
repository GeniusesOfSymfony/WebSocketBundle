<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * @internal
 */
final class RegisterPeriodicTimersListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private PeriodicRegistry $periodicRegistry)
    {
    }

    public function __invoke(ServerLaunchedEvent $event): void
    {
        $loop = $event->getEventLoop();

        foreach ($this->periodicRegistry->getPeriodics() as $periodic) {
            $interval = $periodic->getInterval();

            $loop->addPeriodicTimer($interval, [$periodic, 'tick']);

            $this->logger?->info(
                sprintf(
                    'Registered periodic callback %s, executed every %d seconds',
                    \get_class($periodic),
                    $interval
                )
            );
        }
    }
}
