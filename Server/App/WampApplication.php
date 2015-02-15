<?php

namespace Gos\Bundle\WebSocketBundle\Server\App;

use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * @var ClientStorage
     */
    protected $clientStorage;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param RpcDispatcherInterface   $rpcDispatcher
     * @param TopicDispatcherInterface $topicDispatcher
     * @param EventDispatcherInterface $eventDispatcher
     * @param ClientStorage            $clientStorage
     * @param LoggerInterface          $logger
     */
    public function __construct(
        RpcDispatcherInterface $rpcDispatcher,
        TopicDispatcherInterface $topicDispatcher,
        EventDispatcherInterface $eventDispatcher,
        ClientStorage $clientStorage,
        LoggerInterface $logger = null
    ) {
        $this->rpcDispatcher = $rpcDispatcher;
        $this->topicDispatcher = $topicDispatcher;
        $this->eventDispatcher = $eventDispatcher;
        $this->clientStorage = $clientStorage;
        $this->logger = $logger;
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
        if (null !== $this->logger) {
            $user = $this->clientStorage->getClient($conn->resourceId);
            $username = $user instanceof UserInterface ? $user->getUsername() : $user;

            $this->logger->info(sprintf(
                '%s subscribe to %s',
                $username,
                $topic->getId()
            ));
        }

        $this->topicDispatcher->onSubscribe($conn, $topic);
    }

    /**
     * @param ConnectionInterface        $conn
     * @param \Ratchet\Wamp\Topic|string $topic
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        if (null !== $this->logger) {
            $user = $this->clientStorage->getClient($conn->resourceId);
            $username = $user instanceof UserInterface ? $user->getUsername() : $user;

            $this->logger->info(sprintf(
                'User %s unsubscribed to %s',
                $username,
                $topic->getId()
            ));
        }

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
