<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', RegisterPushHandlersListener::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
final class RegisterPushHandlersListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ServerPushHandlerRegistry $pushHandlerRegistry;
    private PushableWampServerInterface $wampServer;

    public function __construct(ServerPushHandlerRegistry $pushHandlerRegistry, PushableWampServerInterface $wampServer)
    {
        $this->pushHandlerRegistry = $pushHandlerRegistry;
        $this->wampServer = $wampServer;
    }

    public function registerPushHandlers(ServerLaunchedEvent $event): void
    {
        $loop = $event->getEventLoop();

        foreach ($this->pushHandlerRegistry->getPushers() as $handler) {
            try {
                $handler->handle($loop, $this->wampServer);
            } catch (\Exception $e) {
                if (null !== $this->logger) {
                    $this->logger->error(
                        $e->getMessage(),
                        [
                            'exception' => $e,
                            'push_handler_name' => $handler->getName(),
                        ]
                    );
                }
            }
        }
    }
}
