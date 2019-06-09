<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\ServerBuilder;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\OriginCheck;
use Gos\Bundle\WebSocketBundle\Server\App\WampApplication;
use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\Http\HttpServer;
use Ratchet\Session\SessionProvider;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServerBuilderTest extends TestCase
{
    /**
     * @var MockObject|LoopInterface
     */
    private $loop;

    /**
     * @var MockObject|WampApplication
     */
    private $wampApplication;

    /**
     * @var MockObject|TopicManager
     */
    private $topicManager;

    /**
     * @var OriginRegistry
     */
    private $originRegistry;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ServerBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loop = $this->createMock(LoopInterface::class);
        $this->wampApplication = $this->createMock(WampApplication::class);
        $this->topicManager = $this->createMock(TopicManager::class);
        $this->originRegistry = new OriginRegistry();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->builder = new ServerBuilder(
            $this->loop,
            $this->wampApplication,
            $this->topicManager,
            $this->originRegistry,
            $this->eventDispatcher,
            false,
            false,
            30
        );
    }

    public function testTheMessageStackIsBuiltWithoutOptionalDecorators()
    {
        $server = $this->builder->buildMessageStack();

        $this->assertInstanceOf(HttpServer::class, $server, 'The assembled message stack should be returned.');

        $this->assertAttributeInstanceOf(
            WsServer::class,
            '_httpServer',
            $server,
            'The assembled message stack should decorate the correct class.'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testTheMessageStackIsBuiltWithTheSessionProviderDecorator()
    {
        $this->builder->setSessionHandler($this->createMock(\SessionHandlerInterface::class));

        $server = $this->builder->buildMessageStack();

        $this->assertInstanceOf(HttpServer::class, $server, 'The assembled message stack should be returned.');

        $this->assertAttributeInstanceOf(
            SessionProvider::class,
            '_httpServer',
            $server,
            'The assembled message stack should decorate the correct class.'
        );

        $decoratedServer = $this->readAttribute($server, '_httpServer');

        $this->assertAttributeInstanceOf(
            WsServer::class,
            '_app',
            $decoratedServer,
            'The assembled message stack should decorate the correct class.'
        );
    }

    public function testTheMessageStackIsBuiltWithTheOriginCheckDecorator()
    {
        $builder = new ServerBuilder(
            $this->loop,
            $this->wampApplication,
            $this->topicManager,
            $this->originRegistry,
            $this->eventDispatcher,
            true,
            false,
            30
        );

        $server = $builder->buildMessageStack();

        $this->assertInstanceOf(HttpServer::class, $server, 'The assembled message stack should be returned.');

        $this->assertAttributeInstanceOf(
            OriginCheck::class,
            '_httpServer',
            $server,
            'The assembled message stack should decorate the correct class.'
        );

        $decoratedServer = $this->readAttribute($server, '_httpServer');

        $this->assertAttributeInstanceOf(
            WsServer::class,
            '_component',
            $decoratedServer,
            'The assembled message stack should decorate the correct class.'
        );
    }
}
