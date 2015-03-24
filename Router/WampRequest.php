<?php

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Router\RouteInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class WampRequest
{
    /**
     * @var ParameterBag
     */
    protected $attributes;

    /**
     * @var RouteInterface
     */
    protected $route;

    /**
     * @var string
     */
    protected $routeName;

    /**
     * @param string         $routeName
     * @param RouteInterface $route
     * @param ParameterBag   $attributes
     */
    public function __construct($routeName, $route, ParameterBag $attributes)
    {
        $this->attributes = $attributes;
        $this->route = $route;
        $this->routeName = $routeName;
    }

    /**
     * @return ParameterBag
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return RouteInterface
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }
}
