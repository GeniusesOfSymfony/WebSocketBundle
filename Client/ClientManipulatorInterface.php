<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\User\UserInterface;

interface ClientManipulatorInterface
{
    /**
     * @param ConnectionInterface $connection
     *
     * @return bool|string|UserInterface
     */
    public function getClient(ConnectionInterface $connection);

    /**
     * @param Topic  $topic
     * @param string $username
     *
     * @return array|bool
     */
    public function findByUsername(Topic $topic, $username);

    /**
     * @param Topic $topic
     * @param array $roles
     *
     * @return array|bool
     */
    public function findByRoles(Topic $topic, array $roles);

    /**
     * @param Topic $topic
     * @param bool  $anonymous
     *
     * @return array|bool
     */
    public function getAll(Topic $topic, $anonymous = false);
}
