<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;

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
        return $this->clientStorage->getClient($this->clientStorage->getStorageId($connection));
    }
}
