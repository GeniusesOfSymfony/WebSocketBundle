<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Symfony\Component\HttpFoundation\ParameterBag;

final class WampRequest
{
    public ParameterBag $attributes;
    public Route $route;
    public string $routeName;
    public string $matched;

    public function __construct(string $routeName, Route $route, ParameterBag $attributes, string $matched)
    {
        $this->attributes = $attributes;
        $this->route = $route;
        $this->routeName = $routeName;
        $this->matched = $matched;
    }

    public function getAttributes(): ParameterBag
    {
        @trigger_error(
            sprintf(
                'The %s() method is deprecated and will be removed in 4.0, access the "attributes" property directly.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );

        return $this->attributes;
    }

    public function getRoute(): Route
    {
        @trigger_error(
            sprintf(
                'The %s() method is deprecated and will be removed in 4.0, access the "route" property directly.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );

        return $this->route;
    }

    public function getRouteName(): string
    {
        @trigger_error(
            sprintf(
                'The %s() method is deprecated and will be removed in 4.0, access the "routeName" property directly.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );

        return $this->routeName;
    }

    public function getMatched(): string
    {
        @trigger_error(
            sprintf(
                'The %s() method is deprecated and will be removed in 4.0, access the "matched" property directly.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );

        return $this->matched;
    }
}
