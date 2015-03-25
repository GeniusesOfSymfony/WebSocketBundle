<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Authenticator\AuthenticatorInterface;
use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Psr\Log\LoggerInterface;
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
     * @var AuthenticatorInterface
     */
    protected $authenticator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param AuthenticatorInterface $authenticator
     * @param LoggerInterface        $logger
     */
    public function __construct(AuthenticatorInterface $authenticator, LoggerInterface $logger = null)
    {
        $this->authenticator = $authenticator;
        $this->logger = $logger;
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
    public function getClient($identifier, ConnectionInterface $connection)
    {
        if (false === $this->hasClient($identifier)) { //The client can be exprired by the driver
            $user = $this->authenticator->authenticate($connection);
            $this->addClient($identifier, $user);

            if (null !== $this->logger) {
                $this->logger->info(sprintf(
                   'Reloading expired session of user "%s"',
                    $user instanceof UserInterface ? $user->getUsername() : $user
                ), array(
                    'connection_id' => $connection->resourceId,
                    'session_id' => $connection->WAMP->sessionId,
                    'storage_id' => $connection->WAMP->clientStorageId,
                ));
            }

            return $user;
        }

        $serializedUser = $this->driver->fetch($identifier);

        return unserialize($serializedUser);
    }

    /**
     * {@inheritdoc}
     */
    public static function getStorageId(ConnectionInterface $conn)
    {
        return sha1($conn->resourceId . $conn->WAMP->sessionId);
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
        if ($this->hasClient($identifier)) {
            return $this->driver->delete($identifier);
        }

        return true;
    }
}
