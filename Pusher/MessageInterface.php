<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

interface MessageInterface extends \JsonSerializable
{
    public function getTopic(): string;

    public function getData(): array;
}
