<?php

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Symfony\Component\HttpFoundation\ParameterBag;

class WampRequest
{
    /**
     * @var ParameterBag
     */
    protected $attributes;

    /**
     * @var Route
     */
    protected $route;

    /**
     * @var string
     */
    protected $routeName;

    /** @var  string */
    protected $matched;

    /**
     * @param string       $routeName
     * @param Route        $route
     * @param ParameterBag $attributes
     * @param string       $matched
     */
    public function __construct($routeName, Route $route, ParameterBag $attributes, $matched)
    {
        $this->attributes = $attributes;
        $this->route = $route;
        $this->routeName = $routeName;
        $this->matched = $matched;
    }

    /**
     * @return ParameterBag
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return Route
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

    /**
     * @return string
     */
    public function getMatched()
    {
        return $this->matched;
    }
}
