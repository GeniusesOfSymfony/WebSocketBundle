<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

use Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface;
use React\EventLoop\LoopInterface;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" interface is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', ServerPushHandlerInterface::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
interface ServerPushHandlerInterface
{
    public function handle(LoopInterface $loop, PushableWampServerInterface $app): void;

    public function close(): void;

    public function setName(string $name): void;

    public function getName(): string;
}
