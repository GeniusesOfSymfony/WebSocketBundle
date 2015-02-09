<?php

namespace Gos\Bundle\WebSocketBundle\Server\App;

use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class WampApplication implements WampServerInterface
{
    /**
     * @var TopicDispatcherInterface
     */
    protected $topicDispatcher;

    /**
     * @var RpcDispatcherInterface
     */
    protected $rpcDispatcher;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param RpcDispatcherInterface   $rpcDispatcher
     * @param TopicDispatcherInterface $topicDispatcher
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        RpcDispatcherInterface $rpcDispatcher,
        TopicDispatcherInterface $topicDispatcher,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->rpcDispatcher = $rpcDispatcher;
        $this->topicDispatcher = $topicDispatcher;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ConnectionInterface        $conn
     * @param \Ratchet\Wamp\Topic|string $topic
     * @param string                     $event
     * @param array                      $exclude
     * @param array                      $eligible
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        $this->topicDispatcher->onPublish($conn, $topic, $event, $exclude, $eligible);
    }

    /**
     * @param ConnectionInterface        $conn
     * @param string                     $id
     * @param \Ratchet\Wamp\Topic|string $topic
     * @param array                      $params
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        $this->rpcDispatcher->dispatch($conn, $id, $topic, $params);
    }

    /**
     * @param ConnectionInterface        $conn
     * @param \Ratchet\Wamp\Topic|string $topic
     */
    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        $this->topicDispatcher->onSubscribe($conn, $topic);
    }

    /**
     * @param ConnectionInterface        $conn
     * @param \Ratchet\Wamp\Topic|string $topic
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        $this->topicDispatcher->onUnSubscribe($conn, $topic);
    }

    /**
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $event = new ClientEvent($conn, ClientEvent::CONNECTED);
        $this->eventDispatcher->dispatch(Events::CLIENT_CONNECTED, $event);
    }

    /**
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        $event = new ClientEvent($conn, ClientEvent::DISCONNECTED);
        $this->eventDispatcher->dispatch(Events::CLIENT_DISCONNECTED, $event);

        foreach ($conn->WAMP->subscriptions as $subscription) {
            $this->onUnSubscribe($conn, $subscription);
            $subscription->remove($conn);
        }
    }

    /**
     * @param ConnectionInterface $conn
     * @param \Exception          $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $event = new ClientErrorEvent($conn, ClientEvent::ERROR);

        $event->setException($e);
        $this->eventDispatcher->dispatch(Events::CLIENT_ERROR, $event);
    }
}
