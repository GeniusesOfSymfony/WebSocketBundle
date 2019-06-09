<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface ClientManipulatorInterface
{
    public function getClient(ConnectionInterface $connection): TokenInterface;

    /**
     * @return string|object
     */
    public function getUser(ConnectionInterface $connection);

    /**
     * @return TokenInterface[]|bool
     */
    public function findByUsername(Topic $topic, string $username);

    /**
     * @return TokenInterface[]
     */
    public function findByRoles(Topic $topic, array $roles): array;

    /**
     * @return TokenInterface[]
     */
    public function getAll(Topic $topic, bool $anonymous = false): array;
}
