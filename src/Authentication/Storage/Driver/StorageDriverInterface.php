<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * A storage driver provides the backend storage implementation for the token storage API.
 */
interface StorageDriverInterface
{
    public function clear(): void;

    /**
     * @throws StorageException if the token could not be deleted from storage
     */
    public function delete(string $id): bool;

    /**
     * @throws StorageException       if the token could not be read from storage
     * @throws TokenNotFoundException if a token for the given ID is not found
     */
    public function get(string $id): TokenInterface;

    /**
     * @throws StorageException if the storage could not be checked for token presence
     */
    public function has(string $id): bool;

    /**
     * @throws StorageException if the token could not be saved to storage
     */
    public function store(string $id, TokenInterface $token): bool;
}
