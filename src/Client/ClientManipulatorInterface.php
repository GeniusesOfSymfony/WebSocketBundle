<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

trigger_deprecation('gos/web-socket-bundle', '3.11', 'The "%s" interface is deprecated and will be removed in 4.0, use the new websocket authentication API instead.', ClientManipulatorInterface::class);

/**
 * @deprecated to be removed in 4.0, use the new websocket authentication API instead
 */
interface ClientManipulatorInterface
{
    /**
     * @return ClientConnection[]
     */
    public function findAllByUsername(Topic $topic, string $username): array;

    /**
     * @return ClientConnection[]
     */
    public function findByRoles(Topic $topic, array $roles): array;

    /**
     * @return ClientConnection[]
     */
    public function getAll(Topic $topic, bool $anonymous = false): array;

    public function getClient(ConnectionInterface $connection): TokenInterface;

    /**
     * @return string|\Stringable|UserInterface
     */
    public function getUser(ConnectionInterface $connection);
}
