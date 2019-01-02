<?php

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Router\RouteCollection;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;

class NullPubSubRouter implements RouterInterface
{
    public function generate(string $routeName, array $parameters = []): string
    {
        throw new \Exception('Websocket router is not configured, see doc');
    }

    /**
     * {@inheritdoc}
     */
    public function match(string $channel): array
    {
        throw new \Exception('Websocket router is not configured, see doc');
    }

    public function getCollection(): RouteCollection
    {
        // TODO: Implement getCollection() method.
    }

    public function getName(): string
    {
        // TODO: Implement getName() method.
    }
}
