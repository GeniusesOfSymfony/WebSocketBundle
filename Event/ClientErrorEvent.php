<?php

namespace Gos\Bundle\WebSocketBundle\Event;

class ClientErrorEvent extends ClientEvent
{
    /**
     * @var \Exception
     */
    protected $e;

    public function setException(\Exception $e)
    {
        $this->e = $e;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->e;
    }
}
