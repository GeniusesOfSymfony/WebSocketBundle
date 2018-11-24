<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Dispatcher;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\RPC\RpcResponse;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcher;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\RpcRegistry;
use PHPUnit\Framework\TestCase;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampConnection;
use Symfony\Component\HttpFoundation\ParameterBag;

class RpcDispatcherTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RpcRegistry
     */
    private $rpcRegistry;

    /**
     * @var RpcDispatcher
     */
    private $dispatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->rpcRegistry = $this->createMock(RpcRegistry::class);

        $this->dispatcher = new RpcDispatcher($this->rpcRegistry);
    }

    public function testARpcCallIsDispatchedToItsHandler()
    {
        $handler = new class
        {
            private $called = false;

            public function handleCallback(): RpcResponse
            {
                $this->called = true;

                return new RpcResponse([]);
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->rpcRegistry->expects($this->once())
            ->method('hasRpc')
            ->with('@rpc.handler')
            ->willReturn(true);

        $this->rpcRegistry->expects($this->once())
            ->method('getRpc')
            ->with('@rpc.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', '@rpc.handler');

        $attribs = new ParameterBag();
        $attribs->set('method', 'handleCallback');

        $request = new WampRequest('hello.world', $route, $attribs, true);

        $connection = $this->createMock(WampConnection::class);
        $connection->expects($this->once())
            ->method('callResult');

        $topic = $this->createMock(Topic::class);

        $this->dispatcher->dispatch($connection, 'a1b2c3', $topic, $request, []);

        $this->assertTrue($handler->wasCalled());
    }

    public function testARpcCallFailsWhenItsHandlerIsNotInTheRegistry()
    {
        $handler = new class
        {
            private $called = false;

            public function handleCallback(): RpcResponse
            {
                $this->called = true;

                return new RpcResponse([]);
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->rpcRegistry->expects($this->once())
            ->method('hasRpc')
            ->with('@rpc.handler')
            ->willReturn(false);

        $this->rpcRegistry->expects($this->never())
            ->method('getRpc');

        $route = new Route('hello/world', '@rpc.handler');

        $attribs = new ParameterBag();
        $attribs->set('method', 'handleCallback');

        $request = new WampRequest('hello.world', $route, $attribs, true);

        $connection = $this->createMock(WampConnection::class);
        $connection->expects($this->once())
            ->method('callError');

        $topic = $this->createMock(Topic::class);

        $this->dispatcher->dispatch($connection, 'a1b2c3', $topic, $request, []);

        $this->assertFalse($handler->wasCalled());
    }

    public function testARpcCallFailsWhenTheMethodDoesNotExistOnTheHandler()
    {
        $handler = new class
        {
            private $called = false;

            public function handleCallback(): RpcResponse
            {
                $this->called = true;

                return new RpcResponse([]);
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->rpcRegistry->expects($this->once())
            ->method('hasRpc')
            ->with('@rpc.handler')
            ->willReturn(true);

        $this->rpcRegistry->expects($this->once())
            ->method('getRpc')
            ->with('@rpc.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', '@rpc.handler');

        $attribs = new ParameterBag();
        $attribs->set('method', 'handledCallback');

        $request = new WampRequest('hello.world', $route, $attribs, true);

        $connection = $this->createMock(WampConnection::class);
        $connection->expects($this->once())
            ->method('callError');

        $topic = $this->createMock(Topic::class);

        $this->dispatcher->dispatch($connection, 'a1b2c3', $topic, $request, []);

        $this->assertFalse($handler->wasCalled());
    }

    public function testAThrowableFromAHandlerIsCaughtAndProcessed()
    {
        $handler = new class
        {
            public function handleCallback(): RpcResponse
            {
                throw new \Exception('Testing');
            }
        };

        $this->rpcRegistry->expects($this->once())
            ->method('hasRpc')
            ->with('@rpc.handler')
            ->willReturn(true);

        $this->rpcRegistry->expects($this->once())
            ->method('getRpc')
            ->with('@rpc.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', '@rpc.handler');

        $attribs = new ParameterBag();
        $attribs->set('method', 'handleCallback');

        $request = new WampRequest('hello.world', $route, $attribs, true);

        $connection = $this->createMock(WampConnection::class);
        $connection->expects($this->once())
            ->method('callError');

        $topic = $this->createMock(Topic::class);

        $this->dispatcher->dispatch($connection, 'a1b2c3', $topic, $request, []);
    }

    public function testANullReturnFromAHandlerIsProcessed()
    {
        $handler = new class
        {
            public function handleCallback(): void
            {
                return;
            }
        };

        $this->rpcRegistry->expects($this->once())
            ->method('hasRpc')
            ->with('@rpc.handler')
            ->willReturn(true);

        $this->rpcRegistry->expects($this->once())
            ->method('getRpc')
            ->with('@rpc.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', '@rpc.handler');

        $attribs = new ParameterBag();
        $attribs->set('method', 'handleCallback');

        $request = new WampRequest('hello.world', $route, $attribs, true);

        $connection = $this->createMock(WampConnection::class);
        $connection->expects($this->once())
            ->method('callError');

        $topic = $this->createMock(Topic::class);

        $this->dispatcher->dispatch($connection, 'a1b2c3', $topic, $request, []);
    }
}
