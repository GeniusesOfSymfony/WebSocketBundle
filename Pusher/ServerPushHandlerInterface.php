<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

use Ratchet\Wamp\WampServerInterface;
use React\EventLoop\LoopInterface;

interface ServerPushHandlerInterface
{
    public function handle(LoopInterface $loop, WampServerInterface $app): void;

    public function close(): void;

    public function setName(string $name): void;

    public function getName(): string;
}
