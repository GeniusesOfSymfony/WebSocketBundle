<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

abstract class AbstractServerPushHandler implements ServerPushHandlerInterface
{
    /**
     * @var string
     */
    private $name;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
