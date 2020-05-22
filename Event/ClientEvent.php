<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

use Ratchet\ConnectionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * @abstract to be declared abstract in 3.0
 */
/*abstract*/ class ClientEvent extends Event
{
    /**
     * @deprecated to be removed in 3.0
     */
    public const CONNECTED = 1;

    /**
     * @deprecated to be removed in 3.0
     */
    public const DISCONNECTED = 2;

    /**
     * @deprecated to be removed in 3.0
     */
    public const ERROR = 3;

    /**
     * @deprecated to be removed in 3.0
     */
    public const REJECTED = 4;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var int
     */
    protected $type;

    /**
     * @param int $type {@deprecated} to be removed in 3.0
     */
    public function __construct(ConnectionInterface $connection, int $type)
    {
        $this->connection = $connection;
        $this->type = $type;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @deprecated to be removed in 3.0. Check the event type by the subclass instead
     */
    public function getType(): int
    {
        trigger_deprecation('gos/web-socket-bundle', '2.2', 'The %s() method is deprecated will be removed in 3.0. Check the event type by the subclass instead.', __METHOD__);

        return $this->type;
    }
}
