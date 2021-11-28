<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ConnectionRejectedEvent extends Event
{
    public function __construct(
        public readonly ConnectionInterface $connection,
        public readonly ?RequestInterface $request = null,
    ) {
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    public function hasRequest(): bool
    {
        return null !== $this->request;
    }
}
