<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App;

use Ratchet\MessageComponentInterface;

interface ServerBuilderInterface
{
    public function buildMessageStack(): MessageComponentInterface;
}
