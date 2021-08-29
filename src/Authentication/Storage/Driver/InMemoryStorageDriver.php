<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class InMemoryStorageDriver implements StorageDriverInterface
{
    /**
     * @var array<string, TokenInterface>
     */
    private array $tokens = [];

    public function clear(): void
    {
        $this->tokens = [];
    }

    public function delete(string $id): bool
    {
        unset($this->tokens[$id]);

        return true;
    }

    /**
     * @throws TokenNotFoundException if a token for the given ID is not found
     */
    public function get(string $id): TokenInterface
    {
        if (!$this->has($id)) {
            throw new TokenNotFoundException(sprintf('Token for ID "%s" not found.', $id));
        }

        return $this->tokens[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->tokens[$id]);
    }

    public function store(string $id, TokenInterface $token): bool
    {
        $this->tokens[$id] = $token;

        return true;
    }
}
