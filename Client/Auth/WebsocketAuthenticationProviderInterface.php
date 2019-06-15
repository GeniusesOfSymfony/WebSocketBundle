<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Auth;

use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface WebsocketAuthenticationProviderInterface
{
    public function authenticate(ConnectionInterface $conn): TokenInterface;
}
