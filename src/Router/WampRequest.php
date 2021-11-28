<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Symfony\Component\HttpFoundation\ParameterBag;

final class WampRequest
{
    public function __construct(
        private string $routeName,
        private Route $route,
        private ParameterBag $attributes,
        private string $matched,
    ) {
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
