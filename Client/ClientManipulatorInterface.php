<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface ClientManipulatorInterface
{
    /**
     * @return TokenInterface[]
     */
    public function findAllByUsername(Topic $topic, string $username): array;

    /**
     * @return TokenInterface[]
     */
    public function findByRoles(Topic $topic, array $roles): array;

    /**
     * @return TokenInterface[]|bool
     *
     * @deprecated to be removed in 3.0. Use findAllByUsername() instead.
     */
    public function findByUsername(Topic $topic, string $username);

    /**
     * @return TokenInterface[]
     */
    public function getAll(Topic $topic, bool $anonymous = false): array;

    public function getClient(ConnectionInterface $connection): TokenInterface;

    /**
     * @return string|object
     */
    public function getUser(ConnectionInterface $connection);
}
