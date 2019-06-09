<?php

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

use Symfony\Component\Cache\Adapter\AdapterInterface;

class SymfonyCacheDriverDecorator implements DriverInterface
{
    /**
     * @var AdapterInterface
     */
    protected $cache;

    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return mixed
     */
    public function fetch(string $id)
    {
        $item = $this->cache->getItem((string) $id);

        if (!$item->isHit()) {
            return false;
        }

        return $item->get();
    }

    public function contains(string $id): bool
    {
        return $this->cache->hasItem((string) $id);
    }

    public function save(string $id, $data, int $lifeTime = 0): bool
    {
        $item = $this->cache->getItem((string) $id);
        $item->set($data);

        if ($lifeTime > 0) {
            $item->expiresAfter($lifeTime);
        }

        return $this->cache->save($item);
    }

    public function delete(string $id): bool
    {
        return $this->cache->deleteItem((string) $id);
    }
}
