<?php

namespace Gos\Bundle\WebSocketBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\Wamp\Topic;

interface PushableTopicInterface
{
    /**
     * @param WampRequest  $request
     * @param string|array $data
     */
    public function onPush(Topic $topic, WampRequest $request, $data);
}
