<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

interface TopicDispatcherInterface
{
    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     */
    public function onSubscribe(ConnectionInterface $conn, Topic $topic);

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     */
    public function onUnSubscribe(ConnectionInterface $conn, Topic $topic);

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param string              $event
     * @param array               $exclude
     * @param array               $eligible
     */
    public function onPublish(ConnectionInterface $conn, Topic $topic, $event, array $exclude, array $eligible);

    /**
     * @param string              $event
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param null                $payload
     * @param null                $exclude
     * @param null                $eligible
     *
     * @return bool
     */
    public function dispatch($event, ConnectionInterface $conn, Topic $topic, $payload = null, $exclude = null, $eligible = null);
}
