<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class ClientStorage
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @param DriverInterface $driver
     */
    public function setStorageDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param string $identifier
     *
     * @return string|UserInterface|false
     */
    public function getClient($identifier)
    {
        $result = $this->driver->fetch($identifier);

        if (false === $result) {
            throw new StorageException(sprintf('Client %s not found', $identifier));
        }

        return unserialize($result);
    }

    /**
     * @param ConnectionInterface $conn
     *
     * @return string
     */
    public static function getStorageId(ConnectionInterface $conn)
    {
        return $conn->resourceId;
    }

    /**
     * @param string               $identifier
     * @param string|UserInterface $user
     *
     * @throws StorageException
     */
    public function addClient($identifier, $user)
    {
        if (false === $result = $this->driver->save($identifier, serialize($user), 60 * 15)) {
            throw new StorageException('Unable add client');
        }
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function hasClient($identifier)
    {
        return $this->driver->contains($identifier);
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function removeClient($identifier)
    {
        return $this->driver->delete($identifier);
    }
}
