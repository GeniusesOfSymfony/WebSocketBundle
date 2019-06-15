<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Ratchet\ConnectionInterface;

@trigger_error(
    sprintf('The %s trait is deprecated will be removed in 2.0. Inject a %s instance into your class instead.', WebSocketUserTrait::class, ClientManipulatorInterface::class),
    E_USER_DEPRECATED
);

/**
 * @deprecated to be removed in 2.0. Inject a ClientManipulatorInterface instance into your class instead.
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
     *
     * @deprecated to be removed in 2.0. Inject a ClientManipulatorInterface instance into your class instead.
     */
    public function getCurrentUser(ConnectionInterface $connection)
    {
        return $this->clientStorage->getClient($this->clientStorage->getStorageId($connection));
    }
}
