<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

interface DriverInterface
{
    public function fetch(string $id): mixed;

    public function contains(string $id): bool;

    public function save(string $id, mixed $data, int $lifeTime = 0): bool;

    public function delete(string $id): bool;

    public function clear(): void;
}
