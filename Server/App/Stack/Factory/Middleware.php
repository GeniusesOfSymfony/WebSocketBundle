<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack\Factory;

use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\HandshakeMiddlewareAbstract;
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
     * @var HandshakeMiddlewareAbstract
     */
    protected $_middleware;

    /**
     * @param MessageComponentInterface $component
     * @param HandshakeMiddlewareAbstract  $middleware
     */
    public function __construct(
        MessageComponentInterface $component,
        HandshakeMiddlewareAbstract $middleware
    ) {
        $this->_component = $component;
        $this->_middleware = $middleware;
        $this->_middleware->setComponent($component);
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        return $this->_middleware->onOpen($conn, $request);
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        return $this->_middleware->onMessage($from, $msg);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn)
    {
        return $this->_middleware->onClose($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        return $this->_middleware->onError($conn, $e);
    }
}
