<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\Wamp\WampServerInterface;

interface PushableWampServerInterface extends WampServerInterface
{
    public function onPush(WampRequest $request, string | array $data, string $provider): void;
}
