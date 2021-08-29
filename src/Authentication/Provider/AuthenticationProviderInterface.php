<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication\Provider;

use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface AuthenticationProviderInterface
{
    /**
     * Checks to determine if this provider supports the given connection.
     */
    public function supports(ConnectionInterface $connection): bool;

    /**
     * Attempts to authenticate the current connection.
     *
     * Implementations can assume this method will only be executed when supports() is true.
     */
    public function authenticate(ConnectionInterface $connection): TokenInterface;
}
