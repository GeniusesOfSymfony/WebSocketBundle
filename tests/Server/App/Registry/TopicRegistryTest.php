<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

final class TopicRegistryTest extends TestCase
{
    public function testTopicsAreAddedToTheRegistry(): void
    {
        $handler = new class() implements TopicInterface {
            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                // no-op
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                // no-op
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, mixed $event, array $exclude, array $eligible): void
            {
                // no-op
            }

            public function getName(): string
            {
                return 'test';
            }
        };

        $registry = new TopicRegistry([$handler]);

        self::assertSame($handler, $registry->getTopic($handler->getName()));
        self::assertTrue($registry->hasTopic($handler->getName()));
    }

    public function testRetrievingATopicFailsIfTheNamedHandlerDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A topic named "main" has not been registered.');

        $handler = new class() implements TopicInterface {
            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                // no-op
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                // no-op
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, mixed $event, array $exclude, array $eligible): void
            {
                // no-op
            }

            public function getName(): string
            {
                return 'test';
            }
        };

        $registry = new TopicRegistry([$handler]);
        $registry->getTopic('main');
    }
}
