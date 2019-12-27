<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

class ClientErrorEvent extends ClientEvent
{
    protected \Exception $e;

    public function setException(\Exception $e): void
    {
        $this->e = $e;
    }

    public function getException(): \Exception
    {
        return $this->e;
    }
}
