<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

trigger_deprecation('gos/web-socket-bundle', '1.9', 'The %s interface is deprecated will be removed in 2.0. Use the %s class directly instead.', MessageInterface::class, Message::class);

/**
 * @deprecated to be removed in 2.0. Use the Gos\Bundle\WebSocketBundle\Pusher\Message class directly instead.
 */
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
