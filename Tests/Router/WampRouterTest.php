<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Router;

use Gos\Bundle\PubSubRouterBundle\Exception\ResourceNotFoundException;
use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\PubSubRouterBundle\Router\RouteCollection;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Ratchet\Wamp\Topic;

class WampRouterTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface
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

    protected function setUp()
    {
        parent::setUp();

        $this->pubSubRouter = $this->createMock(RouterInterface::class);

        $this->logger = new TestLogger();

        $this->router = new WampRouter($this->pubSubRouter);
        $this->router->setLogger($this->logger);
    }

    public function testATopicIsRouted()
    {
        $routeName = 'test';
        $route = $this->createMock(Route::class);
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

    /**
     * @expectedException \Gos\Bundle\PubSubRouterBundle\Exception\ResourceNotFoundException
     */
    public function testAnExceptionIsThrownWhenATopicCannotBeRouted()
    {
        $routeName = 'test';
        $route = $this->createMock(Route::class);
        $attributes = [];

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->exactly(2))
            ->method('getId')
            ->willReturn('abc/123');

        $this->pubSubRouter->expects($this->once())
            ->method('match')
            ->willThrowException(new ResourceNotFoundException('Testing'));

        $this->router->match($topic);
    }

    public function testARouteIsGenerated()
    {
        $routeName = 'test';
        $parameters = [];

        $this->pubSubRouter->expects($this->once())
            ->method('generate')
            ->with($routeName, $parameters)
            ->willReturn('abc/123');

        $this->assertSame('abc/123', $this->router->generate($routeName, $parameters));
    }

    public function testTheRouteCollectionIsRetrieved()
    {
        $collection = $this->createMock(RouteCollection::class);

        $this->pubSubRouter->expects($this->once())
            ->method('getCollection')
            ->willReturn($collection);

        $this->assertSame($collection, $this->router->getCollection());
    }
}
