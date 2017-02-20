<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack\Factory;

use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\HandshakeMiddlewareInterface;
use Guzzle\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class Middleware implements HttpServerInterface
{
    /**
     * @var \Ratchet\MessageComponentInterface
     */
    protected $_component;

    /**
     * @var HandshakeMiddlewareInterface
     */
    protected $_middleware;

    /**
     * @param MessageComponentInterface $component
     * @param HandshakeMiddlewareInterface  $middleware
     */
    public function __construct(
        MessageComponentInterface $component,
        HandshakeMiddlewareInterface $middleware
    ) {
        $this->_component = $component;
        $this->_middleware = $middleware;
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        $answer = $this->_middleware->onOpen($conn, $request);

        return $answer instanceof ConnectionInterface ? $answer : $this->_component->onOpen($conn, $request);
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->_middleware->onMessage($from, $msg);

        return $this->_component->onMessage($from, $msg);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->_middleware->onClose($conn);

        return $this->_component->onClose($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->_middleware->onError($conn, $e);

        return $this->_component->onError($conn, $e);
    }
}
