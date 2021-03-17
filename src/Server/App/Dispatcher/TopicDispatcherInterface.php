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
    ): void;

    /**
     * @param string|array $data
     *
     * @deprecated method will no longer be required on this interface as of 4.0
     */
    public function onPush(WampRequest $request, $data, string $provider): void;

    /**
     * @param string|array $payload
     *
     * @throws PushUnsupportedException  if the topic does not support push requests
     * @throws \InvalidArgumentException if an unsupported request type is given
     * @throws \RuntimeException         if the connection is missing for a method which requires it or if there is no payload for a push request
     *
     * @deprecated method will no longer be required on this interface as of 4.0
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
