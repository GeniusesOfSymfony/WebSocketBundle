<?php

namespace Gos\Bundle\WebSocketBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;

interface PushableTopicInterface
{
    /**
     * @param WampRequest $request
     * @param string|array            $data
     */
    public function onPush(WampRequest $request, $data);
}
