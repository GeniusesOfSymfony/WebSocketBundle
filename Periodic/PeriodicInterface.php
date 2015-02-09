<?php

namespace Gos\Bundle\WebSocketBundle\Periodic;

interface PeriodicInterface
{
    /**
     * Function excecuted n timeout
     */
    public function tick();

    /**
     * @return int (in millisecond)
     */
    public function getTimeout();
}
