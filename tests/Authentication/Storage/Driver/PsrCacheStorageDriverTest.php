<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Authentication\Storage\Driver;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver\PsrCacheStorageDriver;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\User;

final class PsrCacheStorageDriverTest extends TestCase
{
    /**
     * @var ArrayAdapter
     */
    private $cache;

    /**
     * @var PsrCacheStorageDriver
     */
    private $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new ArrayAdapter();

        $this->driver = new PsrCacheStorageDriver($this->cache);
    }

    public function testTokenIsManagedInStorage(): void
    {
        if (class_exists(InMemoryUser::class)) {
            $user = new InMemoryUser('user', 'password');
        } else {
            $user = new User('user', 'password');
        }

        // Symfony 5.4 deprecates the `$credentials` argument of the token class
        if (3 === (new \ReflectionClass(UsernamePasswordToken::class))->getConstructor()->getNumberOfParameters()) {
            $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);
        } else {
            $token = new UsernamePasswordToken($user, 'password', 'main', ['ROLE_USER']);
        }

        self::assertFalse($this->driver->has('abc'));
        self::assertTrue($this->driver->store('abc', $token));
        self::assertTrue($this->driver->has('abc'));

        $storedToken = $this->driver->get('abc');

        if (method_exists($token, 'getUserIdentifier')) {
            self::assertSame($token->getUserIdentifier(), $storedToken->getUserIdentifier(), 'The token retrieved from storage should be comparable to the originally saved token.');
        } else {
            self::assertSame($token->getUsername(), $storedToken->getUsername(), 'The token retrieved from storage should be comparable to the originally saved token.');
        }

        self::assertTrue($this->driver->delete('abc'));

        try {
            $this->driver->get('abc');

            self::fail('The get() method should throw an exception when the ID is not present.');
        } catch (TokenNotFoundException $exception) {
            // Successful test case
        }

        self::assertTrue($this->driver->store('abc', $token));
        self::assertTrue($this->driver->has('abc'));

        $this->driver->clear();

        self::assertFalse($this->driver->has('abc'));
    }
}
