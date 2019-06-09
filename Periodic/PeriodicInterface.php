<?php

namespace Gos\Bundle\WebSocketBundle\Periodic;

interface PeriodicInterface
{
    public function tick(): void;

    public function getTimeout(): int;
}
