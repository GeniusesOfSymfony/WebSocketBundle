<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface ClientManipulatorInterface
{
    /**
     * @param ConnectionInterface $connection
     *
     * @return TokenInterface
     */
    public function getClient(ConnectionInterface $connection): TokenInterface;

    /**
     * @param ConnectionInterface $connection
     *
     * @return string|object
     */
    public function getUser(ConnectionInterface $connection);

    /**
     * @param Topic  $topic
     * @param string $username
     *
     * @return TokenInterface[]|bool
     */
    public function findByUsername(Topic $topic, $username);

    /**
     * @param Topic $topic
     * @param array $roles
     *
     * @return TokenInterface[]
     */
    public function findByRoles(Topic $topic, array $roles): array;

    /**
     * @param Topic $topic
     * @param bool  $anonymous
     *
     * @return TokenInterface[]
     */
    public function getAll(Topic $topic, $anonymous = false): array;
}
