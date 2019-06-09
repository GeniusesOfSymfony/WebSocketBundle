<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use PHPUnit\Framework\TestCase;
use Ratchet\Wamp\WampServerInterface;
use React\EventLoop\LoopInterface;

class ServerPushHandlerRegistryTest extends TestCase
{
    /**
     * @var ServerPushHandlerRegistry
     */
    private $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new ServerPushHandlerRegistry();
    }

    public function testPushHandlersAreAddedToTheRegistry()
    {
        $handler = new class implements ServerPushHandlerInterface
        {
            public function handle(LoopInterface $loop, WampServerInterface $app): void
            {
                // no-op
            }

            public function close(): void
            {
                // no-op
            }

            public function setName(string $name): void
            {
                // no-op
            }

            public function getName(): string
            {
                return 'test';
            }
        };

        $this->registry->addPushHandler($handler);

        $this->assertSame($handler, $this->registry->getPushHandler($handler->getName()));
        $this->assertContains($handler, $this->registry->getPushers());
        $this->assertTrue($this->registry->hasPushHandler($handler->getName()));
    }

    public function testRetrievingAHandlerFailsIfTheNamedHandlerDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A push handler named "main" has not been registered.');

        $handler = new class implements ServerPushHandlerInterface
        {
            public function handle(LoopInterface $loop, WampServerInterface $app): void
            {
                // no-op
            }

            public function close(): void
            {
                // no-op
            }

            public function setName(string $name): void
            {
                // no-op
            }

            public function getName(): string
            {
                return 'test';
            }
        };

        $this->registry->addPushHandler($handler);

        $this->registry->getPushHandler('main');
    }
}
