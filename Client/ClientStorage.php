<?php

namespace Gos\Bundle\WebSocketBundle\Client;

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
     * @return mixed
     */
    public function getClient($identifier)
    {
        return $this->driver->fetch($identifier);
    }

    /**
     * @param string              $identifier
     * @param UserInterface $user
     */
    public function addClient($identifier, UserInterface $user = null)
    {
        $this->driver->save($identifier, $user);
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