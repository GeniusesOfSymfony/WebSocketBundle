<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use PHPUnit\Framework\TestCase;

class PeriodicRegistryTest extends TestCase
{
    /**
     * @var PeriodicRegistry
     */
    private $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new PeriodicRegistry();
    }

    public function testPeriodicsAreAddedToTheRegistry(): void
    {
        $periodic = new class() implements PeriodicInterface {
            public function tick(): void
            {
                // no-op
            }

            public function getTimeout(): int
            {
                return 10;
            }
        };

        $this->registry->addPeriodic($periodic);

        self::assertContains($periodic, $this->registry->getPeriodics());
        self::assertTrue($this->registry->hasPeriodic($periodic));
    }
}
