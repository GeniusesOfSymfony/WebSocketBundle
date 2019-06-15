<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

interface RpcDispatcherInterface
{
    public function dispatch(
        ConnectionInterface $conn,
        string $id,
        Topic $topic,
        WampRequest $request,
        array $params
    ): void;
}
