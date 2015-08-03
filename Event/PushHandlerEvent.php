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

    /**
     * @param string                     $message
     * @param ServerPushHandlerInterface $pushHandler
     */
    public function __construct($message, ServerPushHandlerInterface $pushHandler)
    {
        $this->message = $message;
        $this->pushHandler = $pushHandler;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return ServerPushHandlerInterface
     */
    public function getPushHandler()
    {
        return $this->pushHandler;
    }
}
