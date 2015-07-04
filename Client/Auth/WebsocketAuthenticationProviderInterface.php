<?php

namespace Gos\Bundle\WebSocketBundle\Client\Auth;

use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface WebsocketAuthenticationProviderInterface
{
    /**
     * @param ConnectionInterface $conn
     *
     * @return TokenInterface
     */
    public function authenticate(ConnectionInterface $conn);
}
