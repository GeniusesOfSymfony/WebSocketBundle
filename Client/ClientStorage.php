<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = null === $logger ? new NullLogger() : $logger;
    }

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

        $this->logger->debug('GET CLIENT ' . $identifier);

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
        $serializedUser = serialize($user);

        $context = [
            'user' => $serializedUser,
        ];

        if ($user instanceof UserInterface) {
            $context['username'] = $user->getUsername();
        }

        $this->logger->debug(sprintf('INSERT CLIENT ' . $identifier), $context);

        if (false === $result = $this->driver->save($identifier, $serializedUser, 60 * 15)) {
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
        $this->logger->debug('REMOVE CLIENT ' . $identifier);

        return $this->driver->delete($identifier);
    }
}
