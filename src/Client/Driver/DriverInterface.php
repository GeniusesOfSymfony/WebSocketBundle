<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

/**
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
