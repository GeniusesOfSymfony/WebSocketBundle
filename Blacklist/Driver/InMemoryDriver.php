<?php

namespace Gos\Bundle\WebSocketBundle\Blacklist;

class InMemoryDriver implements DriverInterface
{
    protected $storage;

    public function __construct()
    {
        $this->storage = array();
    }

    public function block($ip)
    {

    }

    public function unblock($ip)
    {

    }

    public function isBlocked($ip)
    {

    }

    protected function filterAddress($address)
    {
        if (strstr($address, ':') && substr_count($address, '.') == 3) {
            list($address, $port) = explode(':', $address);
        }

        return $address;
    }
}