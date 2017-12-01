<?php

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\ConnectionInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use Ratchet\Wamp\Topic;

interface SecuredTopicInterface
{
    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param null|string         $payload
     * @param string[]|null       $exclude
     * @param string[]|null       $eligible
     * @param string|null         $provider
     *
     * @throws \Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException
     */
    public function secure(ConnectionInterface $conn = null, Topic $topic, WampRequest $request, $payload = null, $exclude = null, $eligible = null, $provider = null);
}
