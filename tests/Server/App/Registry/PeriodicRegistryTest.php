<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use PHPUnit\Framework\TestCase;

final class PeriodicRegistryTest extends TestCase
{
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

        $registry = new PeriodicRegistry([$periodic]);

        self::assertContains($periodic, $registry->getPeriodics());
        self::assertTrue($registry->hasPeriodic($periodic));
    }
}
