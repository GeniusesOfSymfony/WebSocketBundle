<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Guzzle\Http\Message\RequestInterface;
use Ratchet\Http\HttpServerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * @author Tkachew <7tkachew@gmail.com>
 */
abstract class HandshakeMiddlewareAbstract implements HttpServerInterface
{
    /**
     * @var MessageComponentInterface
     */
    protected $_component;

    /**
     * @param MessageComponentInterface $component
     */
    public function setComponent(MessageComponentInterface $component)
    {
        $this->_component = $component;
    }

    /**
     * @param ConnectionInterface $conn
     * @param RequestInterface|null $request
     * @return mixed
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        return $this->_component->onOpen($conn, $request);
    }

    /**
     * @param ConnectionInterface $conn
     * @return mixed
     */
    public function onClose(ConnectionInterface $conn)
    {
        return $this->_component->onClose($conn);
    }

    /**
     * @param ConnectionInterface $conn
     * @param \Exception $e
     * @return mixed
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        return $this->_component->onError($conn, $e);
    }

    /**
     * @param ConnectionInterface $from
     * @param string $msg
     * @return mixed
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        return $this->_component->onMessage($from, $msg);
    }
}
