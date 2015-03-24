<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface ClientStorageInterface
{
    /**
     * @param DriverInterface $driver
     */
    public function setStorageDriver(DriverInterface $driver);

    /**
     * @param string              $identifier
     * @param ConnectionInterface $connection
     *
     * @throws StorageException
     *
     * @return string|UserInterface|false
     */
    public function getClient($identifier, ConnectionInterface $connection);

    /**
     * @param ConnectionInterface $conn
     *
     * @return string
     */
    public static function getStorageId(ConnectionInterface $conn);

    /**
     * @param string               $identifier
     * @param string|UserInterface $user
     *
     * @throws StorageException
     */
    public function addClient($identifier, $user);

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function hasClient($identifier);

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function removeClient($identifier);
}
