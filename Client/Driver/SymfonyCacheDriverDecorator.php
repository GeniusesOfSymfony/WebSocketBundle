<?php

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

use Symfony\Component\Cache\Adapter\AdapterInterface;

class SymfonyCacheDriverDecorator implements DriverInterface
{
    /**
     * @var AdapterInterface
     */
    protected $cache;

    /**
     * @param AdapterInterface $cache
     */
    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    public function fetch($id)
    {
        $item = $this->cache->getItem((string) $id);

        if (!$item->isHit()) {
            return false;
        }

        return $item->get();
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function contains($id)
    {
        return $this->cache->hasItem((string) $id);
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
        $item = $this->cache->getItem((string) $id);
        $item->set($data);

        if ($lifeTime > 0) {
            $item->expiresAfter($lifeTime);
        }

        return $this->cache->save($item);
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
        return $this->cache->deleteItem((string) $id);
    }
}
