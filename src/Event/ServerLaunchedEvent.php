<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ServerLaunchedEvent extends Event
{
    public function __construct(
        public readonly LoopInterface $loop,
        public readonly ServerInterface $server,
        public readonly bool $profile
    ) {
    }

    public function getEventLoop(): LoopInterface
    {
        return $this->loop;
    }

    public function getServer(): ServerInterface
    {
        return $this->server;
    }

    public function isProfiling(): bool
    {
        return $this->profile;
    }
}
