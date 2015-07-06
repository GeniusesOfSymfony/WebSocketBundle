<?php

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Router\RouteCollection;
use Gos\Bundle\PubSubRouterBundle\Router\RouteInterface;
use Gos\Bundle\PubSubRouterBundle\Router\RouterContext;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;

class NullPubSubRouter implements RouterInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate($routeName, Array $parameters = [], $tokenSeparator = null)
    {
        throw new \Exception('Websocket router is not configured, see doc');
    }

    /**
     * {@inheritdoc}
     */
    public function generateFromTokens(RouteInterface $route, Array $tokens, Array $parameters = [], $tokenSeparator)
    {
        throw new \Exception('Websocket router is not configured, see doc');
    }

    /**
     * {@inheritdoc}
     */
    public function match($channel, $tokenSeparator = null)
    {
        throw new \Exception('Websocket router is not configured, see doc');
    }

    /**
     * {@inheritdoc}
     */
    public function setCollection(RouteCollection $collection)
    {
        throw new \Exception('Websocket router is not configured, see doc');
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RouterContext $context)
    {
        throw new \Exception('Websocket router is not configured, see doc');
    }

    /**
     * @return RouterContext
     */
    public function getContext()
    {
        // TODO: Implement getContext() method.
    }

    /**
     * @return RouteCollection
     */
    public function getCollection()
    {
        // TODO: Implement getCollection() method.
    }

    /**
     * @return string
     */
    public function getName()
    {
        // TODO: Implement getName() method.
    }
}
