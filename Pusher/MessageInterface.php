<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

interface MessageInterface extends \JsonSerializable
{
    public function getTopic(): string;

    public function getData(): array;
}
