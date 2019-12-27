<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class PushHandlerEvent extends Event
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
