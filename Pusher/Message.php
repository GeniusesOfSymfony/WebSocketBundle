<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

class Message implements MessageInterface
{
    /** @var string  */
    protected $topic;

    /** @var array  */
    protected $data;

    /**
     * @param string $topic
     * @param array  $data
     */
    public function __construct($topic, $data)
    {
        $this->name = $topic;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getData()
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
