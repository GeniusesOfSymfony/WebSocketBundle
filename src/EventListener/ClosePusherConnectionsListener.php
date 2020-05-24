<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', ClosePusherConnectionsListener::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
final class ClosePusherConnectionsListener
{
    private PusherRegistry $pusherRegistry;

    public function __construct(PusherRegistry $pusherRegistry)
    {
        $this->pusherRegistry = $pusherRegistry;
    }

    public function closeConnection(TerminateEvent $event): void
    {
        foreach ($this->pusherRegistry->getPushers() as $pusher) {
            $pusher->close();
        }
    }
}
