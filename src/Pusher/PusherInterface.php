<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

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
