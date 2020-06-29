<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Ratchet\ConnectionInterface;

final class ClientErrorEvent extends ClientEvent
{
    private \Throwable $throwable;

    public function __construct(\Throwable $throwable, ConnectionInterface $connection)
    {
        parent::__construct($connection);

        $this->throwable = $throwable;
    }

    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }
}
