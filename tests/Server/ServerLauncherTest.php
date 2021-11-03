<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\ServerLauncher;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ServerLauncherTest extends TestCase
{
    /**
     * @var ServerRegistry
     */
    private $serverRegistry;

    /**
     * @var ServerLauncher
     */
    private $serverLauncher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverRegistry = new ServerRegistry();

        $this->serverLauncher = new ServerLauncher($this->serverRegistry);
    }

    public function testTheFirstServerIsLaunchedWhenNoNameIsGiven(): void
    {
        $serverName = 'default';
        $host = 'localhost';
        $port = 8080;
        $profile = false;

        /** @var MockObject&ServerInterface $server */
        $server = $this->createMock(ServerInterface::class);
        $server->expects(self::once())
            ->method('getName')
            ->willReturn($serverName);

        $server->expects(self::once())
            ->method('launch')
            ->with($host, $port, $profile);

        $this->serverRegistry->addServer($server);

        $this->serverLauncher->launch(null, $host, $port, $profile);
    }

    public function testTheNamedServerIsLaunched(): void
    {
        $serverName = 'default';
        $host = 'localhost';
        $port = 8080;
        $profile = false;

        /** @var MockObject&ServerInterface $server */
        $server = $this->createMock(ServerInterface::class);
        $server->expects(self::once())
            ->method('getName')
            ->willReturn($serverName);

        $server->expects(self::once())
            ->method('launch')
            ->with($host, $port, $profile);

        $this->serverRegistry->addServer($server);

        $this->serverLauncher->launch($serverName, $host, $port, $profile);
    }

    public function testAServerIsNotLaunchedWhenTheRegistryIsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('There are no servers registered to launch.');

        $this->serverLauncher->launch(null, 'localhost', 8080, false);
    }

    public function testAServerIsNotLaunchedWhenTheNamedServerIsNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown server test, available servers are [ default ]');

        /** @var MockObject&ServerInterface $server */
        $server = $this->createMock(ServerInterface::class);
        $server->expects(self::once())
            ->method('getName')
            ->willReturn('default');

        $this->serverRegistry->addServer($server);

        $this->serverLauncher->launch('test', 'localhost', 8080, false);
    }
}
