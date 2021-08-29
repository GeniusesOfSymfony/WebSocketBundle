<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Auth;

use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trigger_deprecation('gos/web-socket-bundle', '3.11', 'The "%s" interface is deprecated and will be removed in 4.0, use the new websocket authentication API instead.', WebsocketAuthenticationProviderInterface::class);

/**
 * @deprecated to be removed in 4.0, use the new websocket authentication API instead
 */
interface WebsocketAuthenticationProviderInterface
{
    public function authenticate(ConnectionInterface $conn): TokenInterface;
}
