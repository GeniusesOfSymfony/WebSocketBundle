<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server;

use Gos\Bundle\WebSocketBundle\Server\WampServer;
use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;

class WampServerTest extends TestCase
{
    /**
     * @var MockObject|TopicManager
     */
    private $topicManager;

    /**
     * @var WampServer
     */
    private $serv;

    /**
     * @var MockObject|ConnectionInterface
     */
    private $conn;

    protected function setUp(): void
    {
        $this->topicManager = $this->createMock(TopicManager::class);

        $this->serv = new WampServer($this->topicManager);

        $this->conn = $this->createMock(ConnectionInterface::class);

        $this->serv->onOpen($this->conn);
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
