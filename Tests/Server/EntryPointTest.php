<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Server;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\EntryPoint;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use PHPUnit\Framework\TestCase;

class EntryPointTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ServerRegistry
     */
    private $serverRegistry;

    /**
     * @var EntryPoint
     */
    private $entryPoint;

    protected function setUp()
    {
        parent::setUp();

        $this->serverRegistry = $this->createMock(ServerRegistry::class);

        $this->entryPoint = new EntryPoint($this->serverRegistry);
    }

    public function testTheFirstServerIsLaunchedWhenNoNameIsGiven()
    {
        $serverName = 'default';
        $host = 'localhost';
        $port = 8080;
        $profile = false;

        $server = $this->createMock(ServerInterface::class);
        $server->expects($this->once())
            ->method('launch')
            ->with($host, $port, $profile);

        $this->serverRegistry->expects($this->once())
            ->method('getServers')
            ->willReturn([$serverName => $server]);

        $this->entryPoint->launch(null, $host, $port, $profile);
    }

    public function testTheNamedServerIsLaunched()
    {
        $serverName = 'default';
        $host = 'localhost';
        $port = 8080;
        $profile = false;

        $server = $this->createMock(ServerInterface::class);
        $server->expects($this->once())
            ->method('launch')
            ->with($host, $port, $profile);

        $this->serverRegistry->expects($this->once())
            ->method('hasServer')
            ->with($serverName)
            ->willReturn(true);

        $this->serverRegistry->expects($this->once())
            ->method('getServer')
            ->with($serverName)
            ->willReturn($server);

        $this->entryPoint->launch($serverName, $host, $port, $profile);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage There are no servers registered to launch.
     */
    public function testAServerIsNotLaunchedWhenTheRegistryIsEmpty()
    {
        $serverName = 'default';
        $host = 'localhost';
        $port = 8080;
        $profile = false;

        $this->serverRegistry->expects($this->once())
            ->method('getServers')
            ->willReturn([]);

        $this->entryPoint->launch(null, $host, $port, $profile);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unknown server test, available servers are [ default ]
     */
    public function testAServerIsNotLaunchedWhenTheNamedServerIsNotFound()
    {
        $host = 'localhost';
        $port = 8080;
        $profile = false;

        $this->serverRegistry->expects($this->once())
            ->method('hasServer')
            ->with('test')
            ->willReturn(false);

        $this->serverRegistry->expects($this->once())
            ->method('getServers')
            ->willReturn(['default' => $this->createMock(ServerInterface::class)]);

        $this->serverRegistry->expects($this->never())
            ->method('getServer');

        $this->entryPoint->launch('test', $host, $port, $profile);
    }
}
