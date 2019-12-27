<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PushHandlerEvent extends Event
{
    protected string $message;
    protected ServerPushHandlerInterface $pushHandler;

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
