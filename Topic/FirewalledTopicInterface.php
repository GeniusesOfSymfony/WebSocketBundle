<?php

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\ConnectionInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\Wamp\Topic;

interface FirewalledTopicInterface
{
    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param null|string         $payload
     * @param string[]|null       $exclude
     * @param string[]|null       $eligible
     * @param string|null         $provider
     *
     * @return string|null        $error
     */
    public function checkConnection(ConnectionInterface $conn = null, Topic $topic, WampRequest $request, $payload = null, $exclude = null, $eligible = null, $provider = null);
}
