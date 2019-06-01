<?php

namespace Gos\Bundle\WebSocketBundle\Server\App;

use Ratchet\MessageComponentInterface;

/**
 * @internal
 */
interface ServerBuilderInterface
{
    public function buildMessageStack(): MessageComponentInterface;
}
