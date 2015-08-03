<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

interface MessageInterface extends \JsonSerializable
{
    /**
     * @return string
     */
    public function getTopic();

    /**
     * @return array
     */
    public function getData();
}
