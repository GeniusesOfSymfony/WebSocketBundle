<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

abstract class AbstractServerPushHandler implements ServerPushHandlerInterface
{
    private string $name = '';

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
