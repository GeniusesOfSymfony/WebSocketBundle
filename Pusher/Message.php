<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

class Message implements MessageInterface
{
    /** @var string  */
    protected $name;

    /** @var array  */
    protected $data;

    /**
     * @param string $name
     * @param array $data
     */
    public function __construct($name, $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getName()
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

    function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'data' => $this->data
        ];
    }
}
