<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\Exception\PushUnsupportedException;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

interface TopicDispatcherInterface
{
    public function onSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request): void;

    public function onUnSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request): void;

    public function onPublish(
        ConnectionInterface $conn,
        Topic $topic,
        WampRequest $request,
        string | array $event,
        array $exclude,
        array $eligible
    ): void;

    /**
     * @deprecated method will no longer be required on this interface as of 4.0
     */
    public function onPush(WampRequest $request, string | array $data, string $provider): void;
}
