<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Authentication\Storage\Driver;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver\InMemoryStorageDriver;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class InMemoryStorageDriverTest extends TestCase
{
    public function testTokenIsManagedInStorage(): void
    {
        /** @var MockObject&TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);

        $driver = new InMemoryStorageDriver();

        self::assertFalse($driver->has('abc'));
        self::assertTrue($driver->store('abc', $token));
        self::assertTrue($driver->has('abc'));
        self::assertSame($token, $driver->get('abc'));
        self::assertTrue($driver->delete('abc'));

        try {
            $driver->get('abc');

            self::fail('The get() method should throw an exception when the ID is not present.');
        } catch (TokenNotFoundException $exception) {
            // Successful test case
        }

        self::assertTrue($driver->store('abc', $token));
        self::assertTrue($driver->has('abc'));

        $driver->clear();

        self::assertFalse($driver->has('abc'));
    }
}
