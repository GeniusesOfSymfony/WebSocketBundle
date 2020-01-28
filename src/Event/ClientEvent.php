<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Ratchet\ConnectionInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
abstract class ClientEvent extends Event
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}
