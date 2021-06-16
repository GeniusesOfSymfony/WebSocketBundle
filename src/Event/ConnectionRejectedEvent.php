<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ConnectionRejectedEvent extends Event
{
    private ConnectionInterface $connection;
    private ?RequestInterface $request;

    public function __construct(ConnectionInterface $connection, ?RequestInterface $request = null)
    {
        $this->connection = $connection;
        $this->request = $request;
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
