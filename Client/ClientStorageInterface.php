<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface ClientStorageInterface
{
    public function setStorageDriver(DriverInterface $driver): void;

    /**
     * @throws StorageException
     */
    public function getClient(string $identifier): TokenInterface;

    public function getStorageId(ConnectionInterface $conn): string;

    /**
     * @throws StorageException
     */
    public function addClient(string $identifier, TokenInterface $token): void;

    public function hasClient(string $identifier): bool;

    public function removeClient(string $identifier): bool;
}
