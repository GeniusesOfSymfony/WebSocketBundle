<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Ratchet\ConnectionInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class ClientStorage implements ClientStorageInterface
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * {@inheritdoc}
     */
    public function setStorageDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public static function getStorageId(ConnectionInterface $conn)
    {
        return $conn->resourceId;
    }

    /**
     * {@inheritdoc}
     */
    public function addClient($identifier, $user)
    {
        if (false === $result = $this->driver->save($identifier, serialize($user), 60 * 15)) {
            throw new StorageException('Unable add client');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasClient($identifier)
    {
        return $this->driver->contains($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function removeClient($identifier)
    {
        return $this->driver->delete($identifier);
    }
}
