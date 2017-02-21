<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Server\App\Stack\HandshakeMiddlewareAbstract;

/**
 * @author Tkachew <7tkachew@gmail.com>
 */
class HandshakeMiddlewareRegistry
{
    /**
     * @var HandshakeMiddlewareAbstract[]
     */
    protected $middlewares;

    public function __construct()
    {
        $this->middlewares = [];
    }

    /**
     * @param HandshakeMiddlewareAbstract $middleware
     * @throws \Exception
     */
    public function addMiddleware(HandshakeMiddlewareAbstract $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @return HandshakeMiddlewareAbstract[]
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }
}
