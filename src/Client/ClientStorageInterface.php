<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trigger_deprecation('gos/web-socket-bundle', '3.11', 'The "%s" interface is deprecated and will be removed in 4.0, use the new websocket authentication API instead.', ClientStorageInterface::class);

/**
 * @deprecated to be removed in 4.0, use the new websocket authentication API instead
 *
 * @method void removeAllClients()
 */
interface ClientStorageInterface
{
    /**
     * @throws ClientNotFoundException if the specified client could not be found
     * @throws StorageException        if the client could not be read from storage
     */
    public function getClient(string $identifier): TokenInterface;

    public function getStorageId(ConnectionInterface $conn): string;

    /**
     * @throws StorageException if the client could not be saved to storage
     */
    public function addClient(string $identifier, TokenInterface $token): void;

    /**
     * @throws StorageException if there was an error reading from storage
     */
    public function hasClient(string $identifier): bool;

    /**
     * @throws StorageException if there was an error removing the client from storage
     */
    public function removeClient(string $identifier): bool;
}
