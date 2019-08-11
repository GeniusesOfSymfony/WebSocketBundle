<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

interface SecuredTopicInterface
{
    /**
     * @param string|array $payload
     *
     * @throws FirewallRejectionException if the connection is not authorized access to the topic
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
