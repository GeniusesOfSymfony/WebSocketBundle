<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication;

use Ratchet\ConnectionInterface;

interface AuthenticatorInterface
{
    /**
     * Attempts to authenticate the current connection.
     */
    public function authenticate(ConnectionInterface $connection): void;
}
