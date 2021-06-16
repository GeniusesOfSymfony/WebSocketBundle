<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Router;

use Gos\Bundle\PubSubRouterBundle\Exception\ResourceNotFoundException;
use Gos\Bundle\PubSubRouterBundle\Router\RouteCollection;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\Wamp\Topic;
use Symfony\Component\HttpFoundation\ParameterBag;

final class WampRouter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private RouterInterface $pubSubRouter;

    public function __construct(RouterInterface $router)
    {
        $this->pubSubRouter = $router;
    }

    /**
     * @throws ResourceNotFoundException if the Topic cannot be routed
     */
    public function match(Topic $topic): WampRequest
    {
        try {
            [$routeName, $route, $attributes] = $this->pubSubRouter->match($topic->getId());

            if (null !== $this->logger) {
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
            if (null !== $this->logger) {
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

    public function generate(string $routeName, array $parameters = []): string
    {
        return $this->pubSubRouter->generate($routeName, $parameters);
    }

    public function getCollection(): RouteCollection
    {
        return $this->pubSubRouter->getCollection();
    }
}
