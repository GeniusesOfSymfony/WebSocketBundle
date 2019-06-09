<?php

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Symfony\Component\HttpFoundation\ParameterBag;

final class WampRequest
{
    /**
     * @var ParameterBag
     */
    private $attributes;

    /**
     * @var Route
     */
    private $route;

    /**
     * @var string
     */
    private $routeName;

    /**
     * @var string
     */
    private $matched;

    public function __construct(string $routeName, Route $route, ParameterBag $attributes, string $matched)
    {
        $this->attributes = $attributes;
        $this->route = $route;
        $this->routeName = $routeName;
        $this->matched = $matched;
    }

    public function getAttributes(): ParameterBag
    {
        return $this->attributes;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getMatched(): string
    {
        return $this->matched;
    }
}
