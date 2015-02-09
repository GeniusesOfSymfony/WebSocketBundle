<?php

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

interface TopicInterface
{
     /**
      * @param  ConnectionInterface $connection
      * @param  Topic               $topic
      */
     public function onSubscribe(ConnectionInterface $connection, Topic $topic);

     /**
      * @param  ConnectionInterface $connection
      * @param  Topic               $topic
      */
     public function onUnSubscribe(ConnectionInterface $connection, Topic $topic);

     /**
      * @param  ConnectionInterface $connection
      * @param  Topic               $topic
      * @param $event
      * @param  array               $exclude
      * @param  array               $eligible
      */
     public function onPublish(ConnectionInterface $connection, Topic $topic, $event, array $exclude, array $eligible);

     /**
      * @return string
      */
     public function getPrefix();
}
