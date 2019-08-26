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

    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @param array $config
     *
     * @deprecated to be removed in 2.0. Configuration will no longer be automatically injected in server push handlers.
     */
    public function setConfig(array $config);

    /**
     * @return array
     *
     * @deprecated to be removed in 2.0. Configuration will no longer be automatically injected in server push handlers.
     */
    public function getConfig();

    /**
     * @return string
     */
    public function getName();

    public function close();
}
