<?php
namespace Gos\Bundle\WebSocketBundle\Event;

use Ratchet\ConnectionInterface;
use Symfony\Component\EventDispatcher\Event;

class ClientEvent extends Event
{
    const CONNECTED = 1;
    const DISCONNECTED = 2;
    const ERROR = 3;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var int
     */
    protected $type;

    /**
     * @param ConnectionInterface $connection
     * @param int                 $type
     */
    public function __construct(ConnectionInterface $connection, $type)
    {
        $this->connection = $connection;
        $this->type = $type;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }
}
