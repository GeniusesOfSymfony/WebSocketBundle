<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

interface PusherInterface
{
    /**
     * @param MessageInterface $message
     */
    public function push(MessageInterface $message);

    /**
     * @return array
     */
    public function getConfig();
}
