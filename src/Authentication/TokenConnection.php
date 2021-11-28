<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication;

use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class TokenConnection
{
    public function __construct(
        public readonly TokenInterface $token,
        public readonly ConnectionInterface $connection,
    ) {
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
