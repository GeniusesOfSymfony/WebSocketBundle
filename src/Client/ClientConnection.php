<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trigger_deprecation('gos/web-socket-bundle', '3.11', 'The "%s" class is deprecated and will be removed in 4.0, use the new websocket authentication API instead.', ClientConnection::class);

/**
 * @deprecated to be removed in 4.0, use the new websocket authentication API instead
 */
final class ClientConnection
{
    private TokenInterface $client;
    private ConnectionInterface $connection;

    public function __construct(TokenInterface $client, ConnectionInterface $connection)
    {
        $this->client = $client;
        $this->connection = $connection;
    }

    public function getClient(): TokenInterface
    {
        return $this->client;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}
