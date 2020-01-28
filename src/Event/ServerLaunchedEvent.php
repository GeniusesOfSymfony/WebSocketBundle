<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ServerLaunchedEvent extends Event
{
    private LoopInterface $loop;
    private ServerInterface $server;
    private bool $profile;

    public function __construct(LoopInterface $loop, ServerInterface $server, bool $profile)
    {
        $this->loop = $loop;
        $this->server = $server;
        $this->profile = $profile;
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
