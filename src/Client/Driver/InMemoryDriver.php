<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class InMemoryDriver implements DriverInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $elements = [];

    public function fetch(string $id): mixed
    {
        if (!$this->contains($id)) {
            return false;
        }

        return $this->elements[$id];
    }

    public function contains(string $id): bool
    {
        return isset($this->elements[$id]);
    }

    public function save(string $id, mixed $data, int $lifeTime = 0): bool
    {
        $this->elements[$id] = $data;

        return true;
    }

    public function delete(string $id): bool
    {
        unset($this->elements[$id]);

        return true;
    }

    public function clear(): void
    {
        $this->elements = [];
    }
}
