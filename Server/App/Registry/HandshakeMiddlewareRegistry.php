<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Server\App\Stack\HandshakeMiddlewareInterface;

/**
 * @author Tkachew <7tkachew@gmail.com>
 */
class HandshakeMiddlewareRegistry
{
    /**
     * @var HandshakeMiddlewareInterface[]
     */
    protected $middlewares;

    public function __construct()
    {
        $this->middlewares = [];
    }

    /**
     * @param HandshakeMiddlewareInterface $middleware
     * @throws \Exception
     */
    public function addMiddleware(HandshakeMiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @return HandshakeMiddlewareInterface[]
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }
}
