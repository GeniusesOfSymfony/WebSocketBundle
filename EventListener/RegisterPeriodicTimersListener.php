<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use ProxyManager\Proxy\ProxyInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class RegisterPeriodicTimersListener implements LoggerAwareInterface
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

    public function registerPeriodics(ServerLaunchedEvent $event): void
    {
        $loop = $event->getEventLoop();

        foreach ($this->periodicRegistry->getPeriodics() as $periodic) {
            $loop->addPeriodicTimer($periodic->getTimeout(), [$periodic, 'tick']);

            if (null !== $this->logger) {
                $this->logger->info(
                    sprintf(
                        'Registered periodic callback %s, executed every %s seconds',
                        $periodic instanceof ProxyInterface ? get_parent_class($periodic) : \get_class($periodic),
                        $periodic->getTimeout()
                    )
                );
            }
        }
    }
}
