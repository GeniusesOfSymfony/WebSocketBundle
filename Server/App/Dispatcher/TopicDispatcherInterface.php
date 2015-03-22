<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

interface TopicDispatcherInterface
{
    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param WampRequest         $request
     */
    public function onSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request);

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param WampRequest         $request
     */
    public function onUnSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request);

    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param WampRequest         $request
     * @param string              $event
     * @param array               $exclude
     * @param array               $eligible
     */
    public function onPublish(ConnectionInterface $conn, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible);

    /**
     * @param string              $calledMethod
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param WampRequest         $request
     * @param null                $payload
     * @param null                $exclude
     * @param null                $eligible
     *
     * @return bool
     */
    public function dispatch($calledMethod, ConnectionInterface $conn, Topic $topic, WampRequest $request, $payload = null, $exclude = null, $eligible = null);
}
