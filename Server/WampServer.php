<?php

namespace Gos\Bundle\WebSocketBundle\Server;

use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\Wamp\ServerProtocol;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServerInterface;

/**
 * Class WampServer
 *
 * @author Edu Salguero <edusalguero@gmail.com>
 */
class WampServer implements MessageComponentInterface, WsServerInterface
{
    /**
     * @var ServerProtocol
     */
    protected $wampProtocol;

    /**
     * This class just makes it 1 step easier to use Topic objects in WAMP
     * If you're looking at the source code, look in the __construct of this
     *  class and use that to make your application instead of using this
     */
    public function __construct(WampServerInterface $app, TopicManager $topicManager)
    {
        $this->wampProtocol = new ServerProtocol($topicManager);
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->wampProtocol->onOpen($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $conn, $msg)
    {
        try {
            $this->wampProtocol->onMessage($conn, $msg);
        } catch (\Exception $we) {
            $conn->close(1007);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->wampProtocol->onClose($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->wampProtocol->onError($conn, $e);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProtocols()
    {
        return $this->wampProtocol->getSubProtocols();
    }
}
