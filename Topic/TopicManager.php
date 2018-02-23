<?php

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServerInterface;

/**
 * Class TopicManager
 *
 * @author Edu Salguero <edusalguero@gmail.com>
 */
class TopicManager implements WsServerInterface, WampServerInterface
{
    /**
     * @var WampServerInterface
     */
    protected $app;

    /**
     * @var array
     */
    protected $topicLookup = [];

    public function setWampApplication(WampServerInterface $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $conn->WAMP->subscriptions = new \SplObjectStorage;
        $this->app->onOpen($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        $this->app->onCall($conn, $id, $this->getTopic($topic), $params);
    }

    /**
     * {@inheritdoc}
     */
    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        $topicObj = $this->getTopic($topic);

        if ($conn->WAMP->subscriptions->contains($topicObj)) {
            return;
        }

        $this->topicLookup[$topic]->add($conn);
        $conn->WAMP->subscriptions->attach($topicObj);
        $this->app->onSubscribe($conn, $topicObj);
    }

    /**
     * {@inheritdoc}
     */
    public function onUnsubscribe(ConnectionInterface $conn, $topic)
    {
        $topicObj = $this->getTopic($topic);

        if (!$conn->WAMP->subscriptions->contains($topicObj)) {
            return;
        }

        $this->cleanTopic($topicObj, $conn);

        $this->app->onUnsubscribe($conn, $topicObj);
    }

    /**
     * {@inheritdoc}
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        $this->app->onPublish($conn, $this->getTopic($topic), $event, $exclude, $eligible);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->app->onClose($conn);

        foreach ($this->topicLookup as $topic) {
            $this->cleanTopic($topic, $conn);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->app->onError($conn, $e);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProtocols()
    {
        if ($this->app instanceof WsServerInterface) {
            return $this->app->getSubProtocols();
        }

        return [];
    }

    /**
     * @param string
     *
     * @return Topic
     */
    public function getTopic($topic)
    {
        if (!array_key_exists($topic, $this->topicLookup)) {
            $this->topicLookup[$topic] = new Topic($topic);
        }

        return $this->topicLookup[$topic];
    }

    protected function cleanTopic(Topic $topic, ConnectionInterface $conn)
    {
        if ($conn->WAMP->subscriptions->contains($topic)) {
            $conn->WAMP->subscriptions->detach($topic);
        }

        $this->topicLookup[$topic->getId()]->remove($conn);
    }
}
