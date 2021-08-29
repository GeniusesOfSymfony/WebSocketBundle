<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication;

use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface AuthenticatorInterface
{
    /**
     * Attempts to authenticate the current connection.
     */
    public function authenticate(ConnectionInterface $connection): void;
}
