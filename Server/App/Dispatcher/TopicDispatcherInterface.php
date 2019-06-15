<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

interface TopicDispatcherInterface
{
    public function onSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request);

    public function onUnSubscribe(ConnectionInterface $conn, Topic $topic, WampRequest $request);

    /**
     * @param string|array $event
     */
    public function onPublish(
        ConnectionInterface $conn,
        Topic $topic,
        WampRequest $request,
        $event,
        array $exclude,
        array $eligible
    );

    /**
     * @param string|array $data
     */
    public function onPush(WampRequest $request, $data, string $provider);

    /**
     * @param string|array $payload
     */
    public function dispatch(
        string $calledMethod,
        ?ConnectionInterface $conn,
        Topic $topic,
        WampRequest $request,
        $payload = null,
        ?array $exclude = null,
        ?array $eligible = null,
        ?string $provider = null
    ): bool;
}
