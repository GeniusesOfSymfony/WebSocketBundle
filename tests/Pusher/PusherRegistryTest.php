<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher;

use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @group legacy
 */
class PusherRegistryTest extends TestCase
{
    /**
     * @var PusherRegistry
     */
    private $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new PusherRegistry();
    }

    public function testPushersAreAddedToTheRegistry(): void
    {
        $pusher = new class() implements PusherInterface {
            public function push($data, string $routeName, array $routeParameters = [], array $context = []): void
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

        $this->registry->addPusher($pusher);

        self::assertSame($pusher, $this->registry->getPusher($pusher->getName()));
        self::assertContains($pusher, $this->registry->getPushers());
        self::assertTrue($this->registry->hasPusher($pusher->getName()));
    }

    public function testRetrievingAHandlerFailsIfTheNamedHandlerDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A pusher named "main" has not been registered.');

        $pusher = new class() implements PusherInterface {
            public function push($data, string $routeName, array $routeParameters = [], array $context = []): void
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

        $this->registry->addPusher($pusher);

        $this->registry->getPusher('main');
    }
}
