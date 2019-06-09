<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Dispatcher;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
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
     * @var RpcRegistry
     */
    private $rpcRegistry;

    /**
     * @var RpcDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rpcRegistry = new RpcRegistry();

        $this->dispatcher = new RpcDispatcher($this->rpcRegistry);
    }

    public function testARpcCallIsDispatchedToItsHandler()
    {
        $handler = new class implements RpcInterface
        {
            private $called = false;

            public function getName(): string
            {
                return '@rpc.handler';
            }

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

        $this->rpcRegistry->addRpc($handler);

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
        $handler = new class implements RpcInterface
        {
            private $called = false;

            public function getName(): string
            {
                return '@rpc.handler';
            }

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
        $handler = new class implements RpcInterface
        {
            private $called = false;

            public function getName(): string
            {
                return '@rpc.handler';
            }

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

        $this->rpcRegistry->addRpc($handler);

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
        $handler = new class implements RpcInterface
        {
            public function getName(): string
            {
                return '@rpc.handler';
            }

            public function handleCallback(): RpcResponse
            {
                throw new \Exception('Testing');
            }
        };

        $this->rpcRegistry->addRpc($handler);

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
        $handler = new class implements RpcInterface
        {
            public function getName(): string
            {
                return '@rpc.handler';
            }

            public function handleCallback(): void
            {
                return;
            }
        };

        $this->rpcRegistry->addRpc($handler);

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
