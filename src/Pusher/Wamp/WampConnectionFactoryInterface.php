<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Wamp;

use Gos\Component\WebSocketClient\Wamp\Client;

interface WampConnectionFactoryInterface
{
    public function createConnection(): Client;
}
