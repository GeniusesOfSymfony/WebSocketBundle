<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Registry;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class OriginRegistry
{
    /**
     * @var array
     */
    protected $origins;

    public function __construct()
    {
        $this->origins = [];
    }

    /**
     * @param $origin
     */
    public function addOrigin($origin)
    {
        $this->origins[] = $origin;
    }

    /**
     * @return array
     */
    public function getOrigins()
    {
        return $this->origins;
    }
}
