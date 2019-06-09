<?php

namespace Gos\Bundle\WebSocketBundle\Event;

class ClientErrorEvent extends ClientEvent
{
    /**
     * @var \Exception
     */
    protected $e;

    public function setException(\Exception $e): void
    {
        $this->e = $e;
    }

    public function getException(): \Exception
    {
        return $this->e;
    }
}
