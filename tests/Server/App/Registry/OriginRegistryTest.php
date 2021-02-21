<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;
use PHPUnit\Framework\TestCase;

final class OriginRegistryTest extends TestCase
{
    /**
     * @var OriginRegistry
     */
    private $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new OriginRegistry();
    }

    public function testOriginsAreAddedToTheRegistry(): void
    {
        $origin = 'localhost';

        $this->registry->addOrigin($origin);

        $this->assertContains($origin, $this->registry->getOrigins());
    }
}
