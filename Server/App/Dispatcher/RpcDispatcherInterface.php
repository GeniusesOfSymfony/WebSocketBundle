<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;

interface RpcDispatcherInterface
{
    /**
     * @param ConnectionInterface $conn
     * @param string              $id
     * @param TopicInterface      $topic
     * @param array               $params
     */
    public function dispatch(ConnectionInterface $conn, $id, TopicInterface $topic, array $params);
}
