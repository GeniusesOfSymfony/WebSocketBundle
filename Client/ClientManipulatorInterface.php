<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

interface ClientManipulatorInterface
{
    /**
     * @param ConnectionInterface $connection
     *
     * @return false|string|\Symfony\Component\Security\Core\User\UserInterface
     */
    public function getClient(ConnectionInterface $connection);

    /**
     * @param Topic  $topic
     * @param string $username
     *
     * @return array|false
     */
    public function findByUsername(Topic $topic, $username);

    /**
     * @param Topic $topic
     * @param array $roles
     *
     * @return array|false
     */
    public function findByRoles(Topic $topic, array $roles);

    /**
     * @param Topic $topic
     * @param bool  $anonymous
     *
     * @return array|false
     */
    public function getAll(Topic $topic, $anonymous = false);
}
