<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App;

use Ratchet\MessageComponentInterface;

/**
 * @internal
 */
interface ServerBuilderInterface
{
    public function buildMessageStack(): MessageComponentInterface;
}
