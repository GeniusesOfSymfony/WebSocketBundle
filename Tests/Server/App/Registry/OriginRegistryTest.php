<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;
use PHPUnit\Framework\TestCase;

class OriginRegistryTest extends TestCase
{
    /**
     * @var OriginRegistry
     */
    private $registry;

    protected function setUp()
    {
        parent::setUp();

        $this->registry = new OriginRegistry();
    }

    public function testOriginsAreAddedToTheRegistry()
    {
        $origin = 'localhost';

        $this->registry->addOrigin($origin);

        $this->assertContains($origin, $this->registry->getOrigins());
    }
}
