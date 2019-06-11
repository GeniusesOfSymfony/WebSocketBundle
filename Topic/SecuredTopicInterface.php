<?php

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\ConnectionInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use Ratchet\Wamp\Topic;

interface SecuredTopicInterface
{
    /**
     * @param string|array $payload
     *
     * @throws FirewallRejectionException
     */
    public function secure(
        ?ConnectionInterface $conn,
        Topic $topic,
        WampRequest $request,
        $payload = null,
        ?array $exclude = [],
        ?array $eligible = null,
        ?string $provider = null
    ): void;
}
