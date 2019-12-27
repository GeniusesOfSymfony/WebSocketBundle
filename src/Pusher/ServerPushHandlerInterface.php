<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

use Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface;
use React\EventLoop\LoopInterface;

interface ServerPushHandlerInterface
{
    public function handle(LoopInterface $loop, PushableWampServerInterface $app): void;

    public function close(): void;

    public function setName(string $name): void;

    public function getName(): string;
}
