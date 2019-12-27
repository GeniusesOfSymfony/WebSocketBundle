<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

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
     * @return string|object
     */
    public function getUser(ConnectionInterface $connection);
}
