<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use PHPUnit\Framework\TestCase;

final class ServerRegistryTest extends TestCase
{
    public function testServersAreAddedToTheRegistry(): void
    {
        $server = new class() implements ServerInterface {
            public function launch(string $host, int $port, bool $profile): void
            {
                // no-op
            }

            public function getName(): string
            {
                return 'test';
            }
        };

        $registry = new ServerRegistry([$server]);

        $this->assertSame($server, $registry->getServer($server->getName()));
        $this->assertContains($server, $registry->getServers());
        $this->assertTrue($registry->hasServer($server->getName()));
    }

    public function testRetrievingAServerFailsIfTheNamedServerDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A server named "main" has not been registered.');

        $server = new class() implements ServerInterface {
            public function launch(string $host, int $port, bool $profile): void
            {
                // no-op
            }

            public function getName(): string
            {
                return 'test';
            }
        };

        $registry = new ServerRegistry([$server]);
        $registry->getServer('main');
    }
}
