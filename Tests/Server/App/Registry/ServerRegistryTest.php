<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use PHPUnit\Framework\TestCase;

class ServerRegistryTest extends TestCase
{
    /**
     * @var ServerRegistry
     */
    private $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new ServerRegistry();
    }

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

        $this->registry->addServer($server);

        $this->assertSame($server, $this->registry->getServer($server->getName()));
        $this->assertContains($server, $this->registry->getServers());
        $this->assertTrue($this->registry->hasServer($server->getName()));
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

        $this->registry->addServer($server);

        $this->registry->getServer('main');
    }
}
