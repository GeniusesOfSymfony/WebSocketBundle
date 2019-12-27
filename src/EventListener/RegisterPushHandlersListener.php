<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

final class RegisterPushHandlersListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ServerPushHandlerRegistry
     */
    private $pushHandlerRegistry;

    /**
     * @var PushableWampServerInterface
     */
    private $wampServer;

    public function __construct(ServerPushHandlerRegistry $pushHandlerRegistry, PushableWampServerInterface $wampServer)
    {
        $this->pushHandlerRegistry = $pushHandlerRegistry;
        $this->wampServer = $wampServer;
    }

    public function registerPushHandlers(ServerEvent $event): void
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
