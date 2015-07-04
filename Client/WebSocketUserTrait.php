<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;

/**
 * @deprecated Use ClientManipulator instead will be removed in 2.0
 */
trait WebSocketUserTrait
{
    /**
     * @var ClientStorageInterface
     */
    protected $clientStorage;

    /**
     * @param ConnectionInterface $connection
     *
     * @return false|string|\Symfony\Component\Security\Core\User\UserInterface
     */
    public function getCurrentUser(ConnectionInterface $connection)
    {
        @trigger_error('User ClientManipulator service instead, will be remove in 2.0', E_USER_DEPRECATED);

        return $this->clientStorage->getClient($this->clientStorage->getStorageId($connection));
    }
}
