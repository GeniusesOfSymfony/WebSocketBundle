<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface ClientStorageInterface
{
    /**
     * @param DriverInterface $driver
     */
    public function setStorageDriver(DriverInterface $driver);

    /**
     * @param string $identifier
     *
     * @return TokenInterface
     *
     * @throws StorageException
     */
    public function getClient($identifier): TokenInterface;

    /**
     * @param ConnectionInterface $conn
     *
     * @return string
     */
    public function getStorageId(ConnectionInterface $conn);

    /**
     * @param string         $identifier
     * @param TokenInterface $token
     *
     * @throws StorageException
     */
    public function addClient($identifier, TokenInterface $token);

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
