<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Ratchet\ConnectionInterface;

final class ClientErrorEvent extends ClientEvent
{
    public function __construct(
        public readonly \Throwable $throwable,
        ConnectionInterface $connection
    ) {
        parent::__construct($connection);
    }

    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }
}
