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
use Ratchet\Wamp\Topic;

class WampRouterTest extends TestCase
{
    /**
     * @var MockObject|RouterInterface
     */
    private $pubSubRouter;

    /**
     * @var WampRouter
     */
    private $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pubSubRouter = $this->createMock(RouterInterface::class);

        $this->router = new WampRouter($this->pubSubRouter);
    }

    public function testATopicIsRouted(): void
    {
        $routeName = 'test';
        $route = $this->createMock(Route::class);
        $attributes = [];

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::exactly(2))
            ->method('getId')
            ->willReturn('abc/123');

        $this->pubSubRouter->expects(self::once())
            ->method('match')
            ->willReturn([$routeName, $route, $attributes]);

        self::assertInstanceOf(WampRequest::class, $this->router->match($topic));
    }

    /**
     * @group legacy
     */
    public function testAnExceptionIsThrownWhenATopicCannotBeRouted(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('getId')
            ->willReturn('abc/123');

        $this->pubSubRouter->expects(self::once())
            ->method('match')
            ->willThrowException(new ResourceNotFoundException('Testing'));

        $this->router->match($topic);
    }

    public function testARouteIsGenerated(): void
    {
        $routeName = 'test';
        $parameters = [];

        $this->pubSubRouter->expects(self::once())
            ->method('generate')
            ->with($routeName, $parameters)
            ->willReturn('abc/123');

        self::assertSame('abc/123', $this->router->generate($routeName, $parameters));
    }

    public function testTheRouteCollectionIsRetrieved(): void
    {
        $collection = $this->createMock(RouteCollection::class);

        $this->pubSubRouter->expects(self::once())
            ->method('getCollection')
            ->willReturn($collection);

        self::assertSame($collection, $this->router->getCollection());
    }
}
