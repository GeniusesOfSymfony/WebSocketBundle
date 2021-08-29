<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

use Symfony\Component\Cache\Adapter\AdapterInterface;

trigger_deprecation('gos/web-socket-bundle', '3.11', 'The "%s" class is deprecated and will be removed in 4.0, use the new websocket authentication API instead.', SymfonyCacheDriverDecorator::class);

/**
 * @deprecated to be removed in 4.0, use the new websocket authentication API instead
 */
final class SymfonyCacheDriverDecorator implements DriverInterface
{
    private AdapterInterface $cache;

    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    public function fetch(string $id): mixed
    {
        $item = $this->cache->getItem($id);

        if (!$item->isHit()) {
            return false;
        }

        return $item->get();
    }

    public function contains(string $id): bool
    {
        return $this->cache->hasItem($id);
    }

    public function save(string $id, mixed $data, int $lifeTime = 0): bool
    {
        $item = $this->cache->getItem($id);
        $item->set($data);

        if ($lifeTime > 0) {
            $item->expiresAfter($lifeTime);
        }

        return $this->cache->save($item);
    }

    public function delete(string $id): bool
    {
        return $this->cache->deleteItem($id);
    }

    public function clear(): void
    {
        $this->cache->clear();
    }
}
