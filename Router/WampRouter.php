<?php

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Exception\ResourceNotFoundException;
use Gos\Bundle\PubSubRouterBundle\Router\RouteCollection;
use Gos\Bundle\PubSubRouterBundle\Router\Router;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\Wamp\Topic;
use Symfony\Component\HttpFoundation\ParameterBag;

class WampRouter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Router
     */
    protected $pubSubRouter;

    /**
     * @param Router $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->pubSubRouter = $router;
    }

    /**
     * @param Topic $topic
     *
     * @return WampRequest
     *
     * @throws ResourceNotFoundException
     */
    public function match(Topic $topic)
    {
        try {
            list($routeName, $route, $attributes) = $this->pubSubRouter->match($topic->getId());

            if ($this->logger) {
                $this->logger->debug(
                    sprintf(
                        'Matched route "%s"',
                        $routeName
                    ),
                    $attributes
                );
            }

            return new WampRequest($routeName, $route, new ParameterBag($attributes), $topic->getId());
        } catch (ResourceNotFoundException $e) {
            if ($this->logger) {
                $this->logger->error(
                    sprintf(
                        'Unable to find route for %s',
                        $topic->getId()
                    )
                );
            }

            throw $e;
        }
    }

    /**
     * @param string $routeName
     * @param array  $parameters
     *
     * @return string
     */
    public function generate($routeName, array $parameters = [])
    {
        return $this->pubSubRouter->generate($routeName, $parameters);
    }

    /**
     * @return RouteCollection
     */
    public function getCollection()
    {
        return $this->pubSubRouter->getCollection();
    }
}
