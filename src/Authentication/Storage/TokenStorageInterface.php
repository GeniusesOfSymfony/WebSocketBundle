<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication\Storage;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The token storage provides an API for managing TokenInterface objects for all connections to the websocket server.
 */
interface TokenStorageInterface
{
    /**
     * Generates an identifier to be used for the token representing this connection.
     */
    public function generateStorageId(ConnectionInterface $conn): string;

    /**
     * @throws StorageException if the token could not be saved to storage
     */
    public function addToken(string $id, TokenInterface $token): void;

    /**
     * @throws StorageException       if the token could not be read from storage
     * @throws TokenNotFoundException if a token for the specified identifier could not be found
     */
    public function getToken(string $id): TokenInterface;

    /**
     * @throws StorageException if there was an error reading from storage
     */
    public function hasToken(string $id): bool;

    /**
     * @throws StorageException if there was an error removing the token from storage
     */
    public function removeToken(string $id): bool;

    /**
     * @throws StorageException if there was an error removing any token from storage
     */
    public function removeAllTokens(): void;
}
