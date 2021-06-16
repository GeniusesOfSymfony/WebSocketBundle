<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent;
use Gos\Bundle\WebSocketBundle\GosWebSocketEvents;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IpBlackList;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BlockedIpCheck extends IpBlackList
{
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * @param string[] $blockedIpAddresses
     */
    public function __construct(
        MessageComponentInterface $component,
        EventDispatcherInterface $eventDispatcher,
        array $blockedIpAddresses
    ) {
        parent::__construct($component);

        $this->eventDispatcher = $eventDispatcher;

        foreach ($blockedIpAddresses as $ip) {
            $this->blockAddress($ip);
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        if ($this->isBlocked($conn->remoteAddress)) {
            $this->eventDispatcher->dispatch(new ConnectionRejectedEvent($conn), GosWebSocketEvents::CONNECTION_REJECTED);

            return $conn->close();
        }

        return $this->_decorating->onOpen($conn);
    }
}
