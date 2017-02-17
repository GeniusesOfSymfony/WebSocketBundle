<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Server\App\Stack\HandshakeMiddlewareInterface;
use Ratchet\Http\HttpServerInterface;

/**
 * @author Tkachew <7tkachew@gmail.com>
 */
class HandshakeMiddlewareRegistry
{
    /**
     * @var []
     */
    protected $middlewares;

    public function __construct()
    {
        $this->middlewares = [];
    }

    /**
     * @param array $middleware
     * @throws \Exception
     */
    public function addMiddleware($middleware)
    {
        $interfaces = class_implements($middleware['class']);

        if (!isset($interfaces['Ratchet\Http\HttpServerInterface'])) {
            throw new \Exception("'Ratchet\\Http\\HttpServerInterface' in not implemented by '{$middleware['class']}'");
        }

        $arguments = array_merge([$middleware['class']], $middleware['arguments']);

        $this->middlewares[] = $arguments;
    }

    /**
     * @return []
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }
}
