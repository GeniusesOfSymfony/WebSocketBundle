<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Event;

final class ClientErrorEvent extends ClientEvent
{
    private \Exception $e;

    public function setException(\Exception $e): void
    {
        $this->e = $e;
    }

    public function getException(): \Exception
    {
        return $this->e;
    }
}
