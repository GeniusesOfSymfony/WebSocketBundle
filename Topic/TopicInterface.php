<?php

namespace Gos\Bundle\WebSocketBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

interface TopicInterface
{
    /**
      * @param  ConnectionInterface $connection
      * @param  Topic               $topic
      * @param WampRequest $request
      */
     public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request);

     /**
      * @param  ConnectionInterface $connection
      * @param  Topic               $topic
      * @param WampRequest $request
      */
     public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request);

     /**
      * @param  ConnectionInterface $connection
      * @param  Topic               $topic
      * @param WampRequest $request
      * @param $event
      * @param  array               $exclude
      * @param  array               $eligible
      */
     public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible);

     /**
      * @return string
      */
     public function getName();
}
