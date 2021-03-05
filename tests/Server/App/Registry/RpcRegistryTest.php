<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\RpcRegistry;
use PHPUnit\Framework\TestCase;

final class RpcRegistryTest extends TestCase
{
    public function testRpcHandlersAreAddedToTheRegistry(): void
    {
        $handler = new class() implements RpcInterface {
            public function getName(): string
            {
                return 'test';
            }
        };

        $registry = new RpcRegistry([$handler]);

        $this->assertSame($handler, $registry->getRpc($handler->getName()));
        $this->assertTrue($registry->hasRpc($handler->getName()));
    }

    public function testRetrievingAHandlerFailsIfTheNamedHandlerDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A RPC handler named "main" has not been registered.');

        $handler = new class() implements RpcInterface {
            public function getName(): string
            {
                return 'test';
            }
        };

        $registry = new RpcRegistry([$handler]);
        $registry->getRpc('main');
    }
}
