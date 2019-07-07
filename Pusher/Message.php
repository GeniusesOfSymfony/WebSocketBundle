<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

/**
 * @internal
 */
final class Message
{
    /**
     * @var string
     */
    private $topic;

    /**
     * @var array
     */
    private $data;

    public function __construct(string $topic, array $data)
    {
        $this->topic = $topic;
        $this->data = $data;
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
