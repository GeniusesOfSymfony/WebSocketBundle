<?php

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

use Doctrine\Common\Cache\CacheProvider;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class DoctrineCacheDriverDecorator implements DriverInterface
{
    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    public function fetch($id)
    {
        return $this->cacheProvider->fetch($id);
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function contains($id)
    {
        return $this->cacheProvider->contains($id);
    }

    /**
     * @param string $id
     * @param mixed  $data
     * @param int    $lifeTime
     *
     * @return mixed
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return $this->cacheProvider->save($id, $data, $lifeTime);
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete($id)
    {
        return $this->cacheProvider->delete($id);
    }
}
