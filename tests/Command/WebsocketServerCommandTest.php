<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Command;

use Gos\Bundle\WebSocketBundle\Command\WebsocketServerCommand;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\ServerLauncherInterface;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\Console\Tester\CommandTester;

final class WebsocketServerCommandTest extends TestCase
{
    public function testCommandLaunchesWebSocketServer(): void
    {
        /** @var MockObject&ServerLauncherInterface $entryPoint */
        $entryPoint = $this->createMock(ServerLauncherInterface::class);
        $entryPoint->expects(self::once())
            ->method('launch')
            ->with(null, 'localhost', 1337, false);

        $command = new WebsocketServerCommand($entryPoint, 'localhost', 1337, new ServerRegistry());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }

    public function testCommandLaunchesWebSocketServerWithConsoleArgumentsAndOptions(): void
    {
        /** @var MockObject&ServerLauncherInterface $entryPoint */
        $entryPoint = $this->createMock(ServerLauncherInterface::class);
        $entryPoint->expects(self::once())
            ->method('launch')
            ->with('websocket', 'web.socket', 8443, true);

        $command = new WebsocketServerCommand($entryPoint, 'localhost', 1337, new ServerRegistry());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'name' => 'websocket',
                '--host' => 'web.socket',
                '--port' => 8443,
                '--profile' => true,
            ]
        );
    }

    public function dataCommandAutocompletion(): \Generator
    {
        yield 'argument name' => [
            [''],
            ['test'],
        ];
    }

    /**
     * @dataProvider dataCommandAutocompletion
     */
    public function testCommandAutocompletion(array $input, array $suggestions): void
    {
        if (!class_exists(CommandCompletionTester::class)) {
            self::markTestSkipped('Command autocomplete requires symfony/console 5.4 or later.');
        }

        /** @var MockObject&ServerInterface $server */
        $server = $this->createMock(ServerInterface::class);
        $server->expects(self::once())
            ->method('getName')
            ->willReturn('test');

        $registry = new ServerRegistry();
        $registry->addServer($server);

        /** @var MockObject&ServerLauncherInterface $entryPoint */
        $entryPoint = $this->createMock(ServerLauncherInterface::class);

        $command = new WebsocketServerCommand($entryPoint, 'localhost', 1337, $registry);

        $tester = new CommandCompletionTester($command);

        $this->assertSame($suggestions, $tester->complete($input));
    }
}
