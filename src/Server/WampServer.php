<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\Wamp\ServerProtocol;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServerInterface;

class WampServer implements MessageComponentInterface, WsServerInterface
{
    protected ServerProtocol $wampProtocol;

    public function __construct(WampServerInterface $serverComponent)
    {
        $this->wampProtocol = new ServerProtocol($serverComponent);
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->wampProtocol->onOpen($conn);
    }

    /**
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $conn, $msg): void
    {
        try {
            $this->wampProtocol->onMessage($conn, $msg);
        } catch (\Exception $we) {
            $conn->close(1007);
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->wampProtocol->onClose($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $this->wampProtocol->onError($conn, $e);
    }

    public function getSubProtocols(): array
    {
        return $this->wampProtocol->getSubProtocols();
    }
}
