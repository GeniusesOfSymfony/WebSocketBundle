<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;
use Symfony\Component\EventDispatcher\Event;

class PushHandlerEvent extends Event
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @var ServerPushHandlerInterface
     */
    protected $pushHandler;

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
