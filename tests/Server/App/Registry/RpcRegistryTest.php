<?php declare(strict_types=1);

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

    public function testRpcHandlersAreAddedToTheRegistry(): void
    {
        $handler = new class() implements RpcInterface {
            public function getName(): string
            {
                return 'test';
            }
        };

        $this->registry->addRpc($handler);

        self::assertSame($handler, $this->registry->getRpc($handler->getName()));
        self::assertTrue($this->registry->hasRpc($handler->getName()));
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

        $this->registry->addRpc($handler);

        $this->registry->getRpc('main');
    }
}
