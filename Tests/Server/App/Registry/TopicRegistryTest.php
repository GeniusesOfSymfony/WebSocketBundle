<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

class TopicRegistryTest extends TestCase
{
    /**
     * @var TopicRegistry
     */
    private $registry;

    protected function setUp()
    {
        parent::setUp();

        $this->registry = new TopicRegistry();
    }

    public function testTopicsAreAddedToTheRegistry()
    {
        $handler = new class implements TopicInterface
        {
            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                // no-op
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                // no-op
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                // no-op
            }

            public function getName()
            {
                return 'test';
            }
        };

        $this->registry->addTopic($handler);

        $this->assertSame($handler, $this->registry->getTopic($handler->getName()));
        $this->assertTrue($this->registry->hasTopic($handler->getName()));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A topic named "main" has not been registered.
     */
    public function testRetrievingATopicFailsIfTheNamedHandlerDoesNotExist()
    {
        $handler = new class implements TopicInterface
        {
            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                // no-op
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                // no-op
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                // no-op
            }

            public function getName()
            {
                return 'test';
            }
        };

        $this->registry->addTopic($handler);

        $this->registry->getTopic('main');
    }
}
