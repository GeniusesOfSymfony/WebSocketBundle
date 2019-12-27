<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

/**
 * @internal
 */
final class Message
{
    public string $topic;
    public array $data;

    public function __construct(string $topic, array $data)
    {
        $this->topic = $topic;
        $this->data = $data;
    }
}
