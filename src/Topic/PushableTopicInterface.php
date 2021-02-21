<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\Wamp\Topic;

interface PushableTopicInterface
{
    public function onPush(Topic $topic, WampRequest $request, string | array $data, string $provider): void;
}
