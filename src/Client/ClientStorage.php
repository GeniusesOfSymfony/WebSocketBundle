<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trigger_deprecation('gos/web-socket-bundle', '3.11', 'The "%s" class is deprecated and will be removed in 4.0, use the new websocket authentication API instead.', ClientStorage::class);

/**
 * @deprecated to be removed in 4.0, use the new websocket authentication API instead
 */
final class ClientStorage implements ClientStorageInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private DriverInterface $driver;
    private int $ttl;

    public function __construct(DriverInterface $driver, int $ttl)
    {
        $this->driver = $driver;
        $this->ttl = $ttl;
    }

    /**
     * @throws ClientNotFoundException if the specified client could not be found
     * @throws StorageException        if the client could not be read from storage
     */
    public function getClient(string $identifier): TokenInterface
    {
        try {
            $result = $this->driver->fetch($identifier);
        } catch (\Exception $e) {
            throw new StorageException(sprintf('Driver %s failed', self::class), $e->getCode(), $e);
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
                'username' => method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername(),
            ];

            $this->logger->debug('INSERT CLIENT '.$identifier, $context);
        }

        try {
            $result = $this->driver->save($identifier, $serializedUser, $this->ttl);
        } catch (\Exception $e) {
            throw new StorageException(sprintf('Driver %s failed', self::class), $e->getCode(), $e);
        }

        if (false === $result) {
            $username = method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername();

            throw new StorageException(sprintf('Unable to add client "%s" to storage', $username));
        }
    }

    /**
     * @throws StorageException if there was an error reading from storage
     */
    public function hasClient(string $identifier): bool
    {
        try {
            return $this->driver->contains($identifier);
        } catch (\Exception $e) {
            throw new StorageException(sprintf('Driver %s failed', self::class), $e->getCode(), $e);
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
            return $this->driver->delete($identifier);
        } catch (\Exception $e) {
            throw new StorageException(sprintf('Driver %s failed', self::class), $e->getCode(), $e);
        }
    }

    /**
     * @throws StorageException if there was an error removing the clients from storage
     */
    public function removeAllClients(): void
    {
        if (!method_exists($this->driver, 'clear')) {
            return;
        }

        if (null !== $this->logger) {
            $this->logger->debug('REMOVE ALL CLIENTS');
        }

        try {
            $this->driver->clear();
        } catch (\Exception $e) {
            throw new StorageException(sprintf('Driver %s failed', self::class), $e->getCode(), $e);
        }
    }
}
