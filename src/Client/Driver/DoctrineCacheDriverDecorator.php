<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

use Doctrine\Common\Cache\Cache;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
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
}
