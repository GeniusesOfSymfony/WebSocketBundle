<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

abstract class AbstractServerPushHandler implements ServerPushHandlerInterface
{
    /** @var  string */
    private $name;

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
