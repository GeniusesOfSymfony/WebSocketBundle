<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class PsrCacheStorageDriver implements StorageDriverInterface
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function clear(): void
    {
        $this->cache->clear();
    }

    /**
     * @throws StorageException if the token could not be deleted from storage
     */
    public function delete(string $id): bool
    {
        try {
            return $this->cache->deleteItem($id);
        } catch (CacheException $exception) {
            throw new StorageException(sprintf('Could not delete token for ID "%s" from storage.', $id), $exception->getCode(), $exception);
        }
    }

    /**
     * @throws StorageException       if the token could not be read from storage
     * @throws TokenNotFoundException if a token for the given ID is not found
     */
    public function get(string $id): TokenInterface
    {
        try {
            $item = $this->cache->getItem($id);

            if (!$item->isHit()) {
                throw new TokenNotFoundException(sprintf('Token for ID "%s" not found.', $id));
            }

            return $item->get();
        } catch (CacheException $exception) {
            throw new StorageException(sprintf('Could not load token for ID "%s" from storage.', $id), $exception->getCode(), $exception);
        }
    }

    /**
     * @throws StorageException if the storage could not be checked for token presence
     */
    public function has(string $id): bool
    {
        try {
            return $this->cache->hasItem($id);
        } catch (CacheException $exception) {
            throw new StorageException(sprintf('Could not check storage for token with ID "%s".', $id), $exception->getCode(), $exception);
        }
    }

    public function store(string $id, TokenInterface $token): bool
    {
        try {
            $item = $this->cache->getItem($id);
            $item->set($token);

            return $this->cache->save($item);
        } catch (CacheException $exception) {
            throw new StorageException(sprintf('Could not store token for ID "%s" to storage.', $id), $exception->getCode(), $exception);
        }
    }
}
