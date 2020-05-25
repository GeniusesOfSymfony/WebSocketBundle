<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

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
