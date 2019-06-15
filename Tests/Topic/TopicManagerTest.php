<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Topic;

use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServerInterface;

class TopicManagerTest extends TestCase
{
    /**
     * @var MockObject|WampServerInterface
     */
    private $mock;

    /**
     * @var TopicManager
     */
    private $mngr;

    /**
     * @var MockObject|ConnectionInterface
     */
    private $conn;

    protected function setUp(): void
    {
        $this->conn = $this->createMock(ConnectionInterface::class);
        $this->mock = $this->createMock(WampServerInterface::class);

        $this->mngr = new TopicManager();
        $this->mngr->setWampApplication($this->mock);

        $this->conn->WAMP = new \stdClass();
        $this->mngr->onOpen($this->conn);
    }

    public function testGetTopicReturnsTopicObject()
    {
        $class = new \ReflectionClass(TopicManager::class);
        $method = $class->getMethod('getTopic');
        $method->setAccessible(true);

        $topic = $method->invokeArgs($this->mngr, ['The Topic']);

        $this->assertInstanceOf(Topic::class, $topic);
    }

    public function testGetTopicCreatesTopicWithSameName()
    {
        $name = 'The Topic';

        $class = new \ReflectionClass(TopicManager::class);
        $method = $class->getMethod('getTopic');
        $method->setAccessible(true);

        $topic = $method->invokeArgs($this->mngr, [$name]);

        $this->assertEquals($name, $topic->getId());
    }

    public function testGetTopicReturnsSameObject()
    {
        $class = new \ReflectionClass(TopicManager::class);
        $method = $class->getMethod('getTopic');
        $method->setAccessible(true);

        $topic = $method->invokeArgs($this->mngr, ['No copy']);
        $again = $method->invokeArgs($this->mngr, ['No copy']);

        $this->assertSame($topic, $again);
    }

    public function testOnOpen()
    {
        $this->mock->expects($this->once())->method('onOpen');
        $this->mngr->onOpen($this->conn);
    }

    public function testOnCall()
    {
        $id = uniqid();

        $this->mock->expects($this->once())
            ->method('onCall')
            ->with($this->conn, $id, $this->isInstanceOf(Topic::class), []);

        $this->mngr->onCall($this->conn, $id, 'new topic', []);
    }

    public function testOnSubscribeCreatesTopicObject()
    {
        $this->mock->expects($this->once())
            ->method('onSubscribe')
            ->with($this->conn, $this->isInstanceOf(Topic::class));

        $this->mngr->onSubscribe($this->conn, 'new topic');
    }

    public function testTopicIsInConnectionOnSubscribe()
    {
        $name = 'New Topic';

        $class = new \ReflectionClass(TopicManager::class);
        $method = $class->getMethod('getTopic');
        $method->setAccessible(true);

        $topic = $method->invokeArgs($this->mngr, [$name]);

        $this->mngr->onSubscribe($this->conn, $name);

        $this->assertTrue($this->conn->WAMP->subscriptions->contains($topic));
    }

    public function testDoubleSubscriptionFiresOnce()
    {
        $this->mock->expects($this->exactly(1))->method('onSubscribe');

        $this->mngr->onSubscribe($this->conn, 'same topic');
        $this->mngr->onSubscribe($this->conn, 'same topic');
    }

    public function testUnsubscribeEvent()
    {
        $name = 'in and out';
        $this->mock->expects($this->once())
            ->method('onUnsubscribe')
            ->with($this->conn, $this->isInstanceOf(Topic::class));

        $this->mngr->onSubscribe($this->conn, $name);
        $this->mngr->onUnsubscribe($this->conn, $name);
    }

    public function testUnsubscribeFiresOnce()
    {
        $name = 'getting sleepy';
        $this->mock->expects($this->once())
            ->method('onUnsubscribe');

        $this->mngr->onSubscribe($this->conn, $name);
        $this->mngr->onUnsubscribe($this->conn, $name);
        $this->mngr->onUnsubscribe($this->conn, $name);
    }

    public function testUnsubscribeRemovesTopicFromConnection()
    {
        $name = 'Bye Bye Topic';

        $class = new \ReflectionClass(TopicManager::class);
        $method = $class->getMethod('getTopic');
        $method->setAccessible(true);

        $topic = $method->invokeArgs($this->mngr, [$name]);

        $this->mngr->onSubscribe($this->conn, $name);
        $this->mngr->onUnsubscribe($this->conn, $name);

        $this->assertFalse($this->conn->WAMP->subscriptions->contains($topic));
    }

    public function testOnPublishBubbles()
    {
        $msg = 'Cover all the code!';

        $this->mock->expects($this->once())
            ->method('onPublish')
            ->with($this->conn, $this->isInstanceOf(Topic::class), $msg, $this->isType('array'), $this->isType('array'));

        $this->mngr->onPublish($this->conn, 'topic coverage', $msg, [], []);
    }

    public function testOnCloseBubbles()
    {
        $this->mock->expects($this->once())->method('onClose')->with($this->conn);
        $this->mngr->onClose($this->conn);
    }

    protected function topicProvider($name)
    {
        $class = new \ReflectionClass(TopicManager::class);
        $method = $class->getMethod('getTopic');
        $method->setAccessible(true);

        $attribute = $class->getProperty('topicLookup');
        $attribute->setAccessible(true);

        $topic = $method->invokeArgs($this->mngr, [$name]);

        return [$topic, $attribute];
    }

    public function testConnIsRemovedFromTopicOnClose()
    {
        $name = 'State Testing';
        list($topic, $attribute) = $this->topicProvider($name);

        $this->assertCount(1, $attribute->getValue($this->mngr));

        $this->mngr->onSubscribe($this->conn, $name);
        $this->mngr->onClose($this->conn);

        $this->assertFalse($topic->has($this->conn));
    }

    public static function topicConnExpectationProvider()
    {
        return [
            ['onClose', 0],
            ['onUnsubscribe', 0],
        ];
    }

    /**
     * @dataProvider topicConnExpectationProvider
     */
    public function testTopicRetentionFromLeavingConnections($methodCall, $expectation)
    {
        $topicName = 'checkTopic';
        list($topic, $attribute) = $this->topicProvider($topicName);

        $this->mngr->onSubscribe($this->conn, $topicName);
        \call_user_func_array([$this->mngr, $methodCall], [$this->conn, $topicName]);

        $this->assertCount($expectation, $attribute->getValue($this->mngr));
    }

    public function testOnErrorBubbles()
    {
        $e = new \Exception('All work and no play makes Chris a dull boy');
        $this->mock->expects($this->once())->method('onError')->with($this->conn, $e);

        $this->mngr->onError($this->conn, $e);
    }

    public function testGetSubProtocolsReturnsArray()
    {
        $this->assertSame([], $this->mngr->getSubProtocols());
    }

    public function testGetSubProtocolsBubbles()
    {
        $subs = ['hello', 'world'];
        $app = $this->createMock(WsWampServerInterface::class);
        $app->expects($this->once())
            ->method('getSubProtocols')
            ->willReturn($subs);

        $this->mngr->setWampApplication($app);

        $this->assertEquals($subs, $this->mngr->getSubProtocols());
    }
}

interface WsWampServerInterface extends WampServerInterface, WsServerInterface
{
}
