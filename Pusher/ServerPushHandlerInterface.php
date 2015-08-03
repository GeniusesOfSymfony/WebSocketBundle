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
     */
    public function setConfig(array $config);

    /**
     * @return array
     */
    public function getConfig();

    /**
     * @return string
     */
    public function getName();

    public function close();
}
