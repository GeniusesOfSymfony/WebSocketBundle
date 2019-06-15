<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

class Message implements MessageInterface
{
    /**
     * @var string
     */
    protected $topic;

    /**
     * @var array
     */
    protected $data;

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

    public function jsonSerialize()
    {
        return [
            'topic' => $this->topic,
            'data' => $this->data,
        ];
    }
}
