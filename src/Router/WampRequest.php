<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Symfony\Component\HttpFoundation\ParameterBag;

final class WampRequest
{
    public function __construct(
        public readonly string $routeName,
        public readonly Route $route,
        public readonly ParameterBag $attributes,
        public readonly string $matched,
    ) {
    }

    /**
     * @deprecated to be removed in 5.0, read the attributes from the `$attributes` property instead
     */
    public function getAttributes(): ParameterBag
    {
        trigger_deprecation('gos/web-socket-bundle', '4.0', 'The %s() method is deprecated and will be removed in 4.0. Read the attributes from the $attributes property instead.', __METHOD__);

        return $this->attributes;
    }

    /**
     * @deprecated to be removed in 5.0, fetch the route from the `$route` property instead
     */
    public function getRoute(): Route
    {
        trigger_deprecation('gos/web-socket-bundle', '4.0', 'The %s() method is deprecated and will be removed in 5.0. Fetch the route from the $route property instead.', __METHOD__);

        return $this->route;
    }

    /**
     * @deprecated to be removed in 5.0, read the route name from the `$routeName` property instead
     */
    public function getRouteName(): string
    {
        trigger_deprecation('gos/web-socket-bundle', '4.0', 'The %s() method is deprecated and will be removed in 5.0. Read the route name from the $routeName property instead.', __METHOD__);

        return $this->routeName;
    }

    /**
     * @deprecated to be removed in 5.0, read the matched route from the `$matched` property instead
     */
    public function getMatched(): string
    {
        trigger_deprecation('gos/web-socket-bundle', '4.0', 'The %s() method is deprecated and will be removed in 5.0. Read the matched route from the $matched property instead.', __METHOD__);

        return $this->matched;
    }
}
