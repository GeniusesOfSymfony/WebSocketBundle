<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Server;

use Gos\Bundle\WebSocketBundle\Server\WampServer;
use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampConnection;
use Ratchet\Wamp\WampServerInterface;

class WampServerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WampServerInterface
     */
    private $app;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TopicManager
     */
    private $topicManager;

    /**
     * @var WampServer
     */
    private $serv;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConnectionInterface
     */
    private $conn;

    protected function setUp(): void
    {
        $this->app = $this->createMock(WampServerInterface::class);
        $this->topicManager = $this->createMock(TopicManager::class);

        $this->serv = new WampServer($this->app, $this->topicManager);

        $this->conn = $this->createMock(ConnectionInterface::class);

        $this->serv->onOpen($this->conn);
    }

    public function testOpen()
    {
        $this->markTestSkipped(
            'Cannot test decorator is called because the injected application is ignored'
        );

        $this->app->expects($this->once())
            ->method('onOpen')
            ->with(new IsInstanceOf(WampConnection::class));

        $this->serv->onOpen($this->conn);
    }

    public function testOnMessageToEvent()
    {
        $this->markTestSkipped(
            'Cannot test decorator is called because the injected application is ignored'
        );

        $published = 'Client published this message';

        $this->app->expects($this->once())
            ->method('onPublish')
            ->with(
                new IsInstanceOf(WampConnection::class),
                new IsInstanceOf(Topic::class),
                $published,
                [],
                []
            );

        $this->serv->onMessage($this->conn, json_encode([7, 'topic', $published]));
    }

    public function testOnClose()
    {
        $this->markTestSkipped(
            'Cannot test decorator is called because the injected application is ignored'
        );

        $this->app->expects($this->once())
            ->method('onClose')
            ->with(new IsInstanceOf(WampConnection::class));

        $this->serv->onClose($this->conn);
    }

    public function testOnError()
    {
        $this->markTestSkipped(
            'Cannot test decorator is called because the injected application is ignored'
        );

        $e = new \Exception('Whoops!');

        $this->app->expects($this->once())
            ->method('onError')
            ->with(new IsInstanceOf(WampConnection::class), $e);

        $this->serv->onError($this->conn, $e);
    }

    public function testGetSubProtocols()
    {
        $this->assertSame(['wamp'], $this->serv->getSubProtocols());
    }

    public function testConnectionClosesOnInvalidJson()
    {
        $this->conn->expects($this->once())->method('close');
        $this->serv->onMessage($this->conn, 'invalid json');
    }

    public function testConnectionClosesOnProtocolError()
    {
        $this->conn->expects($this->once())->method('close');
        $this->serv->onMessage($this->conn, json_encode(['valid' => 'json', 'invalid' => 'protocol']));
    }
}
