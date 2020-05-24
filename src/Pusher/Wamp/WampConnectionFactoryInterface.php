<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Wamp;

use Gos\Component\WebSocketClient\Wamp\ClientInterface;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" interface is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', WampConnectionFactoryInterface::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
interface WampConnectionFactoryInterface
{
    public function createConnection(): ClientInterface;
}
