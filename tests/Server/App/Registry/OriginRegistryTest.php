<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;
use PHPUnit\Framework\TestCase;

final class OriginRegistryTest extends TestCase
{
    public function testOriginsAreAddedToTheRegistry(): void
    {
        $origin = 'localhost';

        $registry = new OriginRegistry([$origin]);

        self::assertContains($origin, $registry->getOrigins());
    }
}
