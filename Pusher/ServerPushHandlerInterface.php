<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

use Ratchet\Wamp\WampServerInterface;
use React\EventLoop\LoopInterface;

interface ServerPushHandlerInterface
{
    /**
     * @param LoopInterface       $loop
     * @param WampServerInterface $app
     */
    public function handle(LoopInterface $loop, WampServerInterface $app);
}
