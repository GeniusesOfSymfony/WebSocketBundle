<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\RpcRegistry;
use PHPUnit\Framework\TestCase;

class RpcRegistryTest extends TestCase
{
    /**
     * @var RpcRegistry
     */
    private $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new RpcRegistry();
    }

    public function testRpcHandlersAreAddedToTheRegistry()
    {
        $handler = new class implements RpcInterface
        {
            public function getName()
            {
                return 'test';
            }
        };

        $this->registry->addRpc($handler);

        $this->assertSame($handler, $this->registry->getRpc($handler->getName()));
        $this->assertTrue($this->registry->hasRpc($handler->getName()));
    }

    public function testRetrievingAHandlerFailsIfTheNamedHandlerDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A RPC handler named "main" has not been registered.');

        $handler = new class implements RpcInterface
        {
            public function getName()
            {
                return 'test';
            }
        };

        $this->registry->addRpc($handler);

        $this->registry->getRpc('main');
    }
}
