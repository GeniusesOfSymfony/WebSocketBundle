<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class ClientStorage implements ClientStorageInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var int
     */
    private $ttl;

    public function __construct(int $ttl)
    {
        $this->ttl = $ttl;
    }

    /**
     * @throws \RuntimeException if the client storage driver has not been set
     */
    private function getStorageDriver(): DriverInterface
    {
        if (null === $this->driver) {
            throw new \RuntimeException(
                sprintf(
                    'Storage driver not set in "%s". Did you forget to call "%s::setStorageDriver()?',
                    static::class,
                    static::class
                )
            );
        }

        return $this->driver;
    }

    public function setStorageDriver(DriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * @throws ClientNotFoundException if the specified client could not be found
     * @throws StorageException        if the client could not be read from storage
     */
    public function getClient(string $identifier): TokenInterface
    {
        try {
            $result = $this->getStorageDriver()->fetch($identifier);
        } catch (\Exception $e) {
            throw new StorageException(sprintf('Driver %s failed', static::class), $e->getCode(), $e);
        }

        if (null !== $this->logger) {
            $this->logger->debug('GET CLIENT '.$identifier);
        }

        if (false === $result) {
            throw new ClientNotFoundException(sprintf('Client %s not found', $identifier));
        }

        return unserialize($result);
    }

    public function getStorageId(ConnectionInterface $conn): string
    {
        return (string) $conn->resourceId;
    }

    /**
     * @throws StorageException if the client could not be saved to storage
     */
    public function addClient(string $identifier, TokenInterface $token): void
    {
        $serializedUser = serialize($token);

        if (null !== $this->logger) {
            $context = [
                'token' => $token,
                'username' => $token->getUsername(),
            ];

            $this->logger->debug('INSERT CLIENT '.$identifier, $context);
        }

        try {
            $result = $this->getStorageDriver()->save($identifier, $serializedUser, $this->ttl);
        } catch (\Exception $e) {
            throw new StorageException(sprintf('Driver %s failed', static::class), $e->getCode(), $e);
        }

        if (false === $result) {
            throw new StorageException(sprintf('Unable to add client "%s" to storage', $token->getUsername()));
        }
    }

    /**
     * @throws StorageException if there was an error reading from storage
     */
    public function hasClient(string $identifier): bool
    {
        try {
            return $this->getStorageDriver()->contains($identifier);
        } catch (\Exception $e) {
            throw new StorageException(sprintf('Driver %s failed', static::class), $e->getCode(), $e);
        }
    }

    /**
     * @throws StorageException if there was an error removing the client from storage
     */
    public function removeClient(string $identifier): bool
    {
        if (null !== $this->logger) {
            $this->logger->debug('REMOVE CLIENT '.$identifier);
        }

        try {
            return $this->getStorageDriver()->delete($identifier);
        } catch (\Exception $e) {
            throw new StorageException(sprintf('Driver %s failed', static::class), $e->getCode(), $e);
        }
    }
}
