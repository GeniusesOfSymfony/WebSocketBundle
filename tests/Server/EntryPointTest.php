<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\EntryPoint;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EntryPointTest extends TestCase
{
    /**
     * @var ServerRegistry
     */
    private $serverRegistry;

    /**
     * @var EntryPoint
     */
    private $entryPoint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverRegistry = new ServerRegistry();

        $this->entryPoint = new EntryPoint($this->serverRegistry);
    }

    public function testTheFirstServerIsLaunchedWhenNoNameIsGiven(): void
    {
        $serverName = 'default';
        $host = 'localhost';
        $port = 8080;
        $profile = false;

        /** @var MockObject&ServerInterface $server1 */
        $server1 = $this->createMock(ServerInterface::class);
        $server1->expects($this->once())
            ->method('getName')
            ->willReturn($serverName);

        $server1->expects($this->once())
            ->method('launch')
            ->with($host, $port, $profile);

        /** @var MockObject&ServerInterface $server2 */
        $server2 = $this->createMock(ServerInterface::class);
        $server2->expects($this->once())
            ->method('getName')
            ->willReturn('alternate');

        $server2->expects($this->never())
            ->method('launch');

        $this->serverRegistry->addServer($server1);
        $this->serverRegistry->addServer($server2);

        $this->entryPoint->launch(null, $host, $port, $profile);
    }

    public function testTheNamedServerIsLaunched(): void
    {
        $serverName = 'default';
        $host = 'localhost';
        $port = 8080;
        $profile = false;

        /** @var MockObject&ServerInterface $server */
        $server = $this->createMock(ServerInterface::class);
        $server->expects($this->once())
            ->method('getName')
            ->willReturn($serverName);

        $server->expects($this->once())
            ->method('launch')
            ->with($host, $port, $profile);

        $this->serverRegistry->addServer($server);

        $this->entryPoint->launch($serverName, $host, $port, $profile);
    }

    public function testAServerIsNotLaunchedWhenTheRegistryIsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('There are no servers registered to launch.');

        $this->entryPoint->launch(null, 'localhost', 8080, false);
    }

    public function testAServerIsNotLaunchedWhenTheNamedServerIsNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown server test, available servers are [ default ]');

        /** @var MockObject&ServerInterface $server */
        $server = $this->createMock(ServerInterface::class);
        $server->expects($this->once())
            ->method('getName')
            ->willReturn('default');

        $this->serverRegistry->addServer($server);

        $this->entryPoint->launch('test', 'localhost', 8080, false);
    }
}
