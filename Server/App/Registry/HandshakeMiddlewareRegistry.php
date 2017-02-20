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
     * @param array $middleware
     * @throws \Exception
     */
    public function addMiddleware($middleware)
    {
        $interfaces = class_implements($middleware);

        if (!isset($interfaces['Gos\Bundle\WebSocketBundle\Server\App\Stack\HandshakeMiddlewareInterface'])) {
            $className = get_class($middleware);
            throw new \Exception('"Gos\Bundle\WebSocketBundle\Server\App\Stack\HandshakeMiddlewareInterface" in not implemented by "' . $className . '"');
        }

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
