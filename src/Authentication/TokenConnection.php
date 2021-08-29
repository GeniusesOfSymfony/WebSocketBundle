<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication;

use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class TokenConnection
{
    private TokenInterface $token;
    private ConnectionInterface $connection;

    public function __construct(TokenInterface $token, ConnectionInterface $connection)
    {
        $this->token = $token;
        $this->connection = $connection;
    }

    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}
