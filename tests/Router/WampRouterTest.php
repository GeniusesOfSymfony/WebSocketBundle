<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Router;

use Gos\Bundle\PubSubRouterBundle\Exception\ResourceNotFoundException;
use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\PubSubRouterBundle\Router\RouteCollection;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Ratchet\Wamp\Topic;

class WampRouterTest extends TestCase
{
    /**
     * @var MockObject|RouterInterface
     */
    private $pubSubRouter;

    /**
     * @var TestLogger
     */
    private $logger;

    /**
     * @var WampRouter
     */
    private $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pubSubRouter = $this->createMock(RouterInterface::class);

        $this->logger = new TestLogger();

        $this->router = new WampRouter($this->pubSubRouter);
        $this->router->setLogger($this->logger);
    }

    public function testATopicIsRouted(): void
    {
        $routeName = 'test';
        $route = new Route('', 'strlen');
        $attributes = [];

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->exactly(2))
            ->method('getId')
            ->willReturn('abc/123');

        $this->pubSubRouter->expects($this->once())
            ->method('match')
            ->willReturn([$routeName, $route, $attributes]);

        $this->assertInstanceOf(WampRequest::class, $this->router->match($topic));
    }

    public function testAnExceptionIsThrownWhenATopicCannotBeRouted(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->exactly(2))
            ->method('getId')
            ->willReturn('abc/123');

        $this->pubSubRouter->expects($this->once())
            ->method('match')
            ->willThrowException(new ResourceNotFoundException('Testing'));

        $this->router->match($topic);
    }

    public function testARouteIsGenerated(): void
    {
        $routeName = 'test';
        $parameters = [];

        $this->pubSubRouter->expects($this->once())
            ->method('generate')
            ->with($routeName, $parameters)
            ->willReturn('abc/123');

        $this->assertSame('abc/123', $this->router->generate($routeName, $parameters));
    }

    public function testTheRouteCollectionIsRetrieved(): void
    {
        $collection = new RouteCollection();

        $this->pubSubRouter->expects($this->once())
            ->method('getCollection')
            ->willReturn($collection);

        $this->assertSame($collection, $this->router->getCollection());
    }
}
