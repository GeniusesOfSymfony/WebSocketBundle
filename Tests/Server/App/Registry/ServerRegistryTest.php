<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use PHPUnit\Framework\TestCase;

class ServerRegistryTest extends TestCase
{
    /**
     * @var ServerRegistry
     */
    private $registry;

    protected function setUp()
    {
        parent::setUp();

        $this->registry = new ServerRegistry();
    }

    public function testServersAreAddedToTheRegistry()
    {
        $server = new class implements ServerInterface
        {
            public function launch($host, $port, $profile)
            {
                // no-op
            }

            public function getName()
            {
                return 'test';
            }
        };

        $this->registry->addServer($server);

        $this->assertSame($server, $this->registry->getServer($server->getName()));
        $this->assertContains($server, $this->registry->getServers());
        $this->assertTrue($this->registry->hasServer($server->getName()));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A server named "main" has not been registered.
     */
    public function testRetrievingAServerFailsIfTheNamedServerDoesNotExist()
    {
        $server = new class implements ServerInterface
        {
            public function launch($host, $port, $profile)
            {
                // no-op
            }

            public function getName()
            {
                return 'test';
            }
        };

        $this->registry->addServer($server);

        $this->registry->getServer('main');
    }
}
