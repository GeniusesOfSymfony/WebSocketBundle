<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Ratchet\ConnectionInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class ClientEvent extends Event
{
    public const CONNECTED = 1;
    public const DISCONNECTED = 2;
    public const ERROR = 3;
    public const REJECTED = 4;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var int
     */
    protected $type;

    public function __construct(ConnectionInterface $connection, int $type)
    {
        $this->connection = $connection;
        $this->type = $type;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
