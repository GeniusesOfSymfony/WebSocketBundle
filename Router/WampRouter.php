<?php

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Exception\ResourceNotFoundException;
use Gos\Bundle\PubSubRouterBundle\Router\RouteCollection;
use Gos\Bundle\PubSubRouterBundle\Router\Router;
use Gos\Bundle\PubSubRouterBundle\Router\RouterContext;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
     * @deprecated to be removed in 2.0.
     */
    protected $debug;

    /**
     * @param Router $router
     */
    public function __construct(RouterInterface $router = null, $debug, LoggerInterface $logger = null)
    {
        $this->pubSubRouter = $router;
        $this->logger = null === $logger ? new NullLogger() : $logger;
        $this->debug = $debug;
    }

    /**
     * @param RouterContext $context
     *
     * @deprecated to be removed in 2.0.
     */
    public function setContext(RouterContext $context)
    {
        trigger_deprecation('gos/web-socket-bundle', '1.9', 'The %s() method is deprecated will be removed in 2.0.', __METHOD__);

        $this->pubSubRouter->setContext($context);
    }

    /**
     * @return RouterContext
     *
     * @deprecated to be removed in 2.0.
     */
    public function getContext()
    {
        trigger_deprecation('gos/web-socket-bundle', '1.9', 'The %s() method is deprecated will be removed in 2.0.', __METHOD__);

        return $this->pubSubRouter->getContext();
    }

    /**
     * @param Topic       $topic
     * @param string|null $tokenSeparator {@deprecated}
     *
     * @return WampRequest
     *
     * @throws ResourceNotFoundException
     * @throws \Exception
     */
    public function match(Topic $topic, $tokenSeparator = null)
    {
        if ($tokenSeparator !== null) {
            trigger_deprecation('gos/web-socket-bundle', '1.9', 'The $tokenSeparator argument of %s() is deprecated will be removed in 2.0.', __METHOD__);
        }

        try {
            list($routeName, $route, $attributes) = $this->pubSubRouter->match($topic->getId(), $tokenSeparator);

            if ($this->debug) {
                $this->logger->debug(sprintf(
                    'Matched route "%s"',
                    $routeName
                ), $attributes);
            }

            return new WampRequest($routeName, $route, new ParameterBag($attributes), $topic->getId());
        } catch (ResourceNotFoundException $e) {
            $this->logger->error(sprintf(
                'Unable to find route for %s',
                $topic->getId()
            ));

            throw $e;
        }
    }

    /**
     * @param string      $routeName
     * @param array       $parameters
     * @param null|string $tokenSeparator {@deprecated}
     *
     * @return string
     */
    public function generate($routeName, array $parameters = [], $tokenSeparator = null)
    {
        if ($tokenSeparator !== null) {
            trigger_deprecation('gos/web-socket-bundle', '1.9', 'The $tokenSeparator argument of %s() is deprecated will be removed in 2.0.', __METHOD__);
        }

        return $this->pubSubRouter->generate($routeName, $parameters, $tokenSeparator);
    }

    /**
     * @return RouteCollection
     */
    public function getCollection()
    {
        return $this->pubSubRouter->getCollection();
    }
}
