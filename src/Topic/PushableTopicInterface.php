<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\Wamp\Topic;

interface PushableTopicInterface
{
    /**
     * @param string|array $data
     */
    public function onPush(Topic $topic, WampRequest $request, $data, string $provider): void;
}
