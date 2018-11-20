<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Command;

use Gos\Bundle\WebSocketBundle\Command\WebsocketServerCommand;
use Gos\Bundle\WebSocketBundle\Server\EntryPoint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class WebsocketServerCommandTest extends TestCase
{
    public function testCommandLaunchesWebSocketServer()
    {
        $entryPoint = $this->createMock(EntryPoint::class);
        $entryPoint->expects($this->once())
            ->method('launch')
            ->with(null, 'localhost', 1337, false);

        $command = new WebsocketServerCommand($entryPoint, 'localhost', 1337);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }

    public function testCommandLaunchesWebSocketServerWithConsoleArgumentsAndOptions()
    {
        $entryPoint = $this->createMock(EntryPoint::class);
        $entryPoint->expects($this->once())
            ->method('launch')
            ->with('websocket', 'web.socket', 8443, true);

        $command = new WebsocketServerCommand($entryPoint, 'localhost', 1337);

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
}
