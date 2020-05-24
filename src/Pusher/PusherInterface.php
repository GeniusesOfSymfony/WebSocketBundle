<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" interface is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', PusherInterface::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
interface PusherInterface
{
    /**
     * @param string|array $data
     */
    public function push($data, string $routeName, array $routeParameters = [], array $context = []): void;

    public function close(): void;

    public function setName(string $name): void;

    public function getName(): string;
}
