<?php

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Exception\ResourceNotFoundException;
use Gos\Bundle\PubSubRouterBundle\Router\RouteCollection;
use Gos\Bundle\PubSubRouterBundle\Router\Router;
use Gos\Bundle\PubSubRouterBundle\Router\RouterContext;
use Psr\Log\LoggerInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\HttpFoundation\ParameterBag;

class WampRouter
{
    /**
     * @var Router
     */
    protected $pubSubRouter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @param Router $router
     */
    public function __construct(Router $router, $debug, LoggerInterface $logger = null)
    {
        $this->pubSubRouter = $router;
        $this->logger = $logger;
        $this->debug = $debug;

        $context = new RouterContext();
        $context->setTokenSeparator('/');
        $this->setContext($context);
    }

    /**
     * @param RouterContext $context
     */
    public function setContext(RouterContext $context)
    {
        $this->pubSubRouter->setContext($context);
    }

    /**
     * @return RouterContext
     */
    public function getContext()
    {
        return $this->pubSubRouter->getContext();
    }

    /**
     * @param Topic       $topic
     * @param string|null $tokenSeparator
     *
     * @return WampRequest
     */
    public function match(Topic $topic, $tokenSeparator = null)
    {
        try {
            list($routeName, $route, $attributes) = $this->pubSubRouter->match($topic->getId(), '/');
        } catch (ResourceNotFoundException $e) {
            if (null !== $this->logger) {
                $this->logger->error(sprintf(
                    'Unable to find route for %s',
                    $topic->getId()
                ));
            }
        }

        if ($this->debug && null !== $this->logger) {
            $this->logger->debug(sprintf(
                'Matched route "%s"',
                $routeName
            ));
        }

        return new WampRequest($routeName, $route, new ParameterBag($attributes));
    }

    /**
     * @param string $resource
     */
    public function addResource($resource)
    {
        $this->pubSubRouter->addResource($resource);
    }

    public function loadRoute()
    {
        $this->pubSubRouter->loadRoute();
    }

    /**
     * @return bool
     */
    public function isLoaded()
    {
        return $this->pubSubRouter->isLoaded();
    }

    /**
     * @return RouteCollection
     */
    public function getCollection()
    {
        return $this->pubSubRouter->getCollection();
    }
}
