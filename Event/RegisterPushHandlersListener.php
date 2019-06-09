<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\WampApplication;
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
     * @var WampApplication
     */
    private $wampApplication;

    public function __construct(ServerPushHandlerRegistry $pushHandlerRegistry, WampApplication $wampApplication)
    {
        $this->pushHandlerRegistry = $pushHandlerRegistry;
        $this->wampApplication = $wampApplication;
    }

    public function registerPushHandlers(ServerEvent $event): void
    {
        $loop = $event->getEventLoop();

        foreach ($this->pushHandlerRegistry->getPushers() as $handler) {
            try {
                $handler->handle($loop, $this->wampApplication);
            } catch (\Exception $e) {
                if ($this->logger) {
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
