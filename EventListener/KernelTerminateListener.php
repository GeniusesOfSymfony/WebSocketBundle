<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

final class KernelTerminateListener
{
    /**
     * @var PusherRegistry
     */
    private $pusherRegistry;

    public function __construct(PusherRegistry $pusherRegistry)
    {
        $this->pusherRegistry = $pusherRegistry;
    }

    /**
     * @param PostResponseEvent|TerminateEvent $event
     */
    public function closeConnection(object $event): void
    {
        if (!($event instanceof PostResponseEvent) && !($event instanceof TerminateEvent)) {
            throw new \InvalidArgumentException(sprintf('The $event argument of %s() must be an instance of %s or %s, a %s was given.', __METHOD__, PostResponseEvent::class, TerminateEvent::class, \get_class($event)));
        }

        foreach ($this->pusherRegistry->getPushers() as $pusher) {
            $pusher->close();
        }
    }
}
