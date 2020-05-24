<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;
use Symfony\Contracts\EventDispatcher\Event;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', PushHandlerEvent::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
abstract class PushHandlerEvent extends Event
{
    private string $message;
    private ServerPushHandlerInterface $pushHandler;

    public function __construct(string $message, ServerPushHandlerInterface $pushHandler)
    {
        $this->message = $message;
        $this->pushHandler = $pushHandler;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getPushHandler(): ServerPushHandlerInterface
    {
        return $this->pushHandler;
    }
}
