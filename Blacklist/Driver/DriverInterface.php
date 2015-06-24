<?php

namespace Gos\Bundle\WebSocketBundle\Blacklist;

interface DriverInterface
{
    public function block($ip);

    public function unblock($ip);

    public function isBlocked($ip);
}