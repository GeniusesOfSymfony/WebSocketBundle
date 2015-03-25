<?php

namespace Gos\Bundle\WebSocketBundle\Client\Authenticator;

use Ratchet\ConnectionInterface;

interface AuthenticatorInterface
{
    public function authenticate(ConnectionInterface $connection);
}
