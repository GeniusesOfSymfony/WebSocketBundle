<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Topic;

use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServerInterface;

final class TopicManagerTest extends TestCase
{
    /**
     * @var MockObject&WampServerInterface
     */
    private $mock;

    /**
     * @var TopicManager
     */
    private $mngr;

    /**
     * @var MockObject&ConnectionInterface
     */
    private $conn;

    protected function setUp(): void
    {
        $this->conn = $this->createMock(ConnectionInterface::class);
        $this->mock = $this->createMock(WampServerInterface::class);

        $this->mngr = new TopicManager($this->mock);

        $this->conn->WAMP = new \stdClass();
        $this->mngr->onOpen($this->conn);
    }

    public function testGetTopicReturnsTopicObject(): void
    {
        self::assertInstanceOf(Topic::class, $this->mngr->getTopic('The Topic'));
    }

    public function testGetTopicCreatesTopicWithSameName(): void
    {
        $name = 'The Topic';

        $topic = $this->mngr->getTopic($name);

        self::assertEquals($name, $topic->getId());
    }

    public function testGetTopicReturnsSameObject(): void
    {
        self::assertSame(
            $this->mngr->getTopic('The Topic'),
            $this->mngr->getTopic('The Topic')
        );
    }

    public function testGetTopicRejectsInvalidTopicArguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->mngr->getTopic(null);
    }

    public function testOnOpen(): void
    {
        $this->mock->expects(self::once())->method('onOpen');
        $this->mngr->onOpen($this->conn);
    }

    public function testOnCall(): void
    {
        $id = uniqid();

        $this->mock->expects(self::once())
            ->method('onCall')
            ->with($this->conn, $id, self::isInstanceOf(Topic::class), []);

        $this->mngr->onCall($this->conn, $id, 'new topic', []);
    }

    public function testOnSubscribeCreatesTopicObject(): void
    {
        $this->mock->expects(self::once())
            ->method('onSubscribe')
            ->with($this->conn, self::isInstanceOf(Topic::class));

        $this->mngr->onSubscribe($this->conn, 'new topic');
    }

    public function testTopicIsInConnectionOnSubscribe(): void
    {
        $name = 'New Topic';

        $topic = $this->mngr->getTopic($name);

        $this->mngr->onSubscribe($this->conn, $name);

        self::assertTrue($this->conn->WAMP->subscriptions->contains($topic));
    }

    public function testDoubleSubscriptionFiresOnce(): void
    {
        $this->mock->expects(self::exactly(1))->method('onSubscribe');

        $this->mngr->onSubscribe($this->conn, 'same topic');
        $this->mngr->onSubscribe($this->conn, 'same topic');
    }

    public function testUnsubscribeEvent(): void
    {
        $name = 'in and out';
        $this->mock->expects(self::once())
            ->method('onUnsubscribe')
            ->with($this->conn, self::isInstanceOf(Topic::class));

        $this->mngr->onSubscribe($this->conn, $name);
        $this->mngr->onUnsubscribe($this->conn, $name);
    }

    public function testUnsubscribeFiresOnce(): void
    {
        $name = 'getting sleepy';
        $this->mock->expects(self::once())
            ->method('onUnsubscribe');

        $this->mngr->onSubscribe($this->conn, $name);
        $this->mngr->onUnsubscribe($this->conn, $name);
        $this->mngr->onUnsubscribe($this->conn, $name);
    }

    public function testUnsubscribeRemovesTopicFromConnection(): void
    {
        $name = 'Bye Bye Topic';

        $topic = $this->mngr->getTopic($name);

        $this->mngr->onSubscribe($this->conn, $name);
        $this->mngr->onUnsubscribe($this->conn, $name);

        self::assertFalse($this->conn->WAMP->subscriptions->contains($topic));
    }

    public function testOnPublishBubbles(): void
    {
        $msg = 'Cover all the code!';

        $this->mock->expects(self::once())
            ->method('onPublish')
            ->with($this->conn, self::isInstanceOf(Topic::class), $msg, self::isType('array'), self::isType('array'));

        $this->mngr->onPublish($this->conn, 'topic coverage', $msg, [], []);
    }

    public function testOnCloseBubbles(): void
    {
        $this->mock->expects(self::once())->method('onClose')->with($this->conn);
        $this->mngr->onClose($this->conn);
    }

    protected function topicProvider(string $name): array
    {
        $class = new \ReflectionClass(TopicManager::class);
        $attribute = $class->getProperty('topicLookup');
        $attribute->setAccessible(true);

        $topic = $this->mngr->getTopic($name);

        return [$topic, $attribute];
    }

    public function testConnIsRemovedFromTopicOnClose(): void
    {
        $name = 'State Testing';
        [$topic, $attribute] = $this->topicProvider($name);

        self::assertCount(1, $attribute->getValue($this->mngr));

        $this->mngr->onSubscribe($this->conn, $name);
        $this->mngr->onClose($this->conn);

        self::assertFalse($topic->has($this->conn));
    }

    public function topicConnExpectationProvider(): \Generator
    {
        yield ['onClose', 0];
        yield ['onUnsubscribe', 0];
    }

    /**
     * @dataProvider topicConnExpectationProvider
     */
    public function testTopicRetentionFromLeavingConnections(string $methodCall, int $expectation): void
    {
        $topicName = 'checkTopic';
        [$topic, $attribute] = $this->topicProvider($topicName);

        $this->mngr->onSubscribe($this->conn, $topicName);
        $this->mngr->$methodCall($this->conn, $topicName);

        self::assertCount($expectation, $attribute->getValue($this->mngr));
    }

    public function testOnErrorBubbles(): void
    {
        $e = new \Exception('All work and no play makes Chris a dull boy');
        $this->mock->expects(self::once())->method('onError')->with($this->conn, $e);

        $this->mngr->onError($this->conn, $e);
    }

    public function testGetSubProtocolsReturnsArray(): void
    {
        self::assertSame([], $this->mngr->getSubProtocols());
    }

    public function testGetSubProtocolsBubbles(): void
    {
        $subs = ['hello', 'world'];
        $app = $this->createMock(WsWampServerInterface::class);
        $app->expects(self::once())
            ->method('getSubProtocols')
            ->willReturn($subs);

        $mngr = new TopicManager($app);

        self::assertEquals($subs, $mngr->getSubProtocols());
    }
}

interface WsWampServerInterface extends WampServerInterface, WsServerInterface
{
}
