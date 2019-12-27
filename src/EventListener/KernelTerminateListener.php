<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

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

    public function closeConnection(PostResponseEvent $event): void
    {
        foreach ($this->pusherRegistry->getPushers() as $pusher) {
            $pusher->close();
        }
    }
}
