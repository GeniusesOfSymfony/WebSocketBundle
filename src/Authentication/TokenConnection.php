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

    /**
     * @deprecated to be removed in 5.0, read the token from the `$token` property instead
     */
    public function getToken(): TokenInterface
    {
        trigger_deprecation('gos/web-socket-bundle', '4.0', 'The %s() method is deprecated and will be removed in 5.0. Read the token from the $token property instead.', __METHOD__);

        return $this->token;
    }

    /**
     * @deprecated to be removed in 5.0, read the connection from the `$connection` property instead
     */
    public function getConnection(): ConnectionInterface
    {
        trigger_deprecation('gos/web-socket-bundle', '4.0', 'The %s() method is deprecated and will be removed in 5.0. Read the connection from the $connection property instead.', __METHOD__);

        return $this->connection;
    }
}
