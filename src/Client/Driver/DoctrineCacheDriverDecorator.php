<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Symfony\Component\Cache\DoctrineProvider;

trigger_deprecation('gos/web-socket-bundle', '3.4', 'The "%s" class is deprecated and will be removed in 4.0, use the "%s" class with a "%s" instance instead.', DoctrineCacheDriverDecorator::class, SymfonyCacheDriverDecorator::class, DoctrineProvider::class);

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 *
 * @deprecated to be removed in 4.0, use the `Gos\Bundle\WebSocketBundle\Client\Driver\SymfonyCacheDriverDecorator` with a `Symfony\Component\Cache\DoctrineProvider` instance instead
 */
final class DoctrineCacheDriverDecorator implements DriverInterface
{
    private Cache $cacheProvider;

    public function __construct(Cache $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @return mixed
     */
    public function fetch(string $id)
    {
        return $this->cacheProvider->fetch($id);
    }

    public function contains(string $id): bool
    {
        return $this->cacheProvider->contains($id);
    }

    /**
     * @param mixed $data
     */
    public function save(string $id, $data, int $lifeTime = 0): bool
    {
        return $this->cacheProvider->save($id, $data, $lifeTime);
    }

    public function delete(string $id): bool
    {
        return $this->cacheProvider->delete($id);
    }

    public function clear(): void
    {
        if ($this->cacheProvider instanceof ClearableCache) {
            $this->cacheProvider->deleteAll();
        }
    }
}
