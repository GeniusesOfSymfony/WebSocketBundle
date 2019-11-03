<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;

class PushHandlerEvent extends CompatibilityEvent
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
