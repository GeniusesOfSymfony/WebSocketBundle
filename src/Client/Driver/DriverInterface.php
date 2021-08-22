<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

trigger_deprecation('gos/web-socket-bundle', '3.11', 'The "%s" interface is deprecated and will be removed in 4.0, use the new websocket authentication API instead.', DriverInterface::class);

/**
 * @deprecated to be removed in 4.0, use the new websocket authentication API instead
 *
 * @method void clear()
 */
interface DriverInterface
{
    /**
     * @return mixed
     */
    public function fetch(string $id);

    public function contains(string $id): bool;

    /**
     * @param mixed $data
     */
    public function save(string $id, $data, int $lifeTime = 0): bool;

    public function delete(string $id): bool;
}
