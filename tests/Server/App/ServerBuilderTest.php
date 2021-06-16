<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\ServerBuilder;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\BlockedIpCheck;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\OriginCheck;
use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\Http\HttpServer;
use Ratchet\Session\SessionProvider;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ServerBuilderTest extends TestCase
{
    /**
     * @var MockObject|LoopInterface
     */
    private $loop;

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
        $this->topicManager = $this->createMock(TopicManager::class);
        $this->originRegistry = new OriginRegistry();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->builder = new ServerBuilder(
            $this->loop,
            $this->topicManager,
            $this->originRegistry,
            $this->eventDispatcher,
            false,
            false,
            30,
            false,
            []
        );
    }

    public function testTheMessageStackIsBuiltWithoutOptionalDecorators(): void
    {
        $server = $this->builder->buildMessageStack();

        self::assertInstanceOf(HttpServer::class, $server, 'The assembled message stack should be returned.');

        self::assertInstanceOf(
            WsServer::class,
            $this->getPropertyFromClassInstance($server, '_httpServer'),
            'The assembled message stack should decorate the correct class.'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testTheMessageStackIsBuiltWithTheSessionProviderDecorator(): void
    {
        $this->builder->setSessionHandler($this->createMock(\SessionHandlerInterface::class));

        $server = $this->builder->buildMessageStack();

        self::assertInstanceOf(HttpServer::class, $server, 'The assembled message stack should be returned.');

        $decoratedServer = $this->getPropertyFromClassInstance($server, '_httpServer');

        self::assertInstanceOf(
            SessionProvider::class,
            $decoratedServer,
            'The assembled message stack should decorate the correct class.'
        );

        self::assertInstanceOf(
            WsServer::class,
            $this->getPropertyFromClassInstance($decoratedServer, '_app'),
            'The assembled message stack should decorate the correct class.'
        );
    }

    public function testTheMessageStackIsBuiltWithTheOriginCheckDecorator(): void
    {
        $builder = new ServerBuilder(
            $this->loop,
            $this->topicManager,
            $this->originRegistry,
            $this->eventDispatcher,
            true,
            false,
            30,
            false,
            []
        );

        $server = $builder->buildMessageStack();

        self::assertInstanceOf(HttpServer::class, $server, 'The assembled message stack should be returned.');

        $decoratedServer = $this->getPropertyFromClassInstance($server, '_httpServer');

        self::assertInstanceOf(
            OriginCheck::class,
            $decoratedServer,
            'The assembled message stack should decorate the correct class.'
        );

        self::assertInstanceOf(
            WsServer::class,
            $this->getPropertyFromClassInstance($decoratedServer, '_component'),
            'The assembled message stack should decorate the correct class.'
        );
    }

    public function testTheMessageStackIsBuiltWithTheIpAddressCheckDecorator(): void
    {
        $builder = new ServerBuilder(
            $this->loop,
            $this->topicManager,
            $this->originRegistry,
            $this->eventDispatcher,
            false,
            false,
            30,
            true,
            ['192.168.1.1']
        );

        $server = $builder->buildMessageStack();

        self::assertInstanceOf(BlockedIpCheck::class, $server, 'The assembled message stack should be returned.');

        $decoratedServer = $this->getPropertyFromClassInstance($server, '_decorating');

        self::assertInstanceOf(
            HttpServer::class,
            $decoratedServer,
            'The assembled message stack should decorate the correct class.'
        );

        self::assertInstanceOf(
            WsServer::class,
            $this->getPropertyFromClassInstance($decoratedServer, '_httpServer'),
            'The assembled message stack should decorate the correct class.'
        );
    }

    /**
     * @return mixed
     *
     * @throws \InvalidArgumentException if the requested property does not exist on the given class instance
     */
    private function getPropertyFromClassInstance(object $classInstance, string $property)
    {
        $reflClass = new \ReflectionClass($classInstance);

        if (!$reflClass->hasProperty($property)) {
            throw new \InvalidArgumentException(sprintf('The %s class does not have a property named "%s".', \get_class($classInstance), $property));
        }

        $reflProperty = $reflClass->getProperty($property);
        $reflProperty->setAccessible(true);

        return $reflProperty->getValue($classInstance);
    }
}
