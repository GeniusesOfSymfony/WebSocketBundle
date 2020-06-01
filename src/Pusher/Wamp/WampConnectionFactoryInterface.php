<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Wamp;

use Gos\Component\WebSocketClient\Wamp\ClientFactoryInterface;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" interface is deprecated and will be removed in 4.0, use "%s" instead.', WampConnectionFactoryInterface::class, ClientFactoryInterface::class);

/**
 * @deprecated to be removed in 4.0, use the Gos\Component\WebSocketClient\Wamp\ClientFactoryInterface from the gos/websocket-client package instead
 */
interface WampConnectionFactoryInterface extends ClientFactoryInterface
{
}
