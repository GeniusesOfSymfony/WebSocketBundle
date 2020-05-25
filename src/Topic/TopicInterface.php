<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

interface TopicInterface
{
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void;

    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void;

    /**
     * @param mixed $event The event data
     */
    public function onPublish(
        ConnectionInterface $connection,
        Topic $topic,
        WampRequest $request,
        $event,
        array $exclude,
        array $eligible
    ): void;

    public function getName(): string;
}
