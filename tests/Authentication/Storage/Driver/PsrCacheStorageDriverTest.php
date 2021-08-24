<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Authentication\Storage\Driver;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver\PsrCacheStorageDriver;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

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
        $token = new TestToken('my-test-user@example.com');

        self::assertFalse($this->driver->has('abc'));
        self::assertTrue($this->driver->store('abc', $token));
        self::assertTrue($this->driver->has('abc'));

        $storedToken = $this->driver->get('abc');

        self::assertSame($token->getUserIdentifier(), $storedToken->getUserIdentifier(), 'The token retrieved from storage should be comparable to the originally saved token.');

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

final class TestToken implements TokenInterface
{
    private UserInterface $user;

    public function __construct(string $identifier)
    {
        if (class_exists(InMemoryUser::class)) {
            $this->user = new InMemoryUser($identifier, null);
        } else {
            $this->user = new User($identifier, null);
        }
    }

    public function __toString(): string
    {
        return sprintf('%s(user="%s")', self::class, $this->getUserIdentifier());
    }

    public function getRoleNames(): array
    {
        return [];
    }

    public function getCredentials(): string
    {
        return '';
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser($user): void
    {
        throw new \BadMethodCallException(sprintf('Cannot set user on %s.', self::class));
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getUserIdentifier(): string
    {
        if (method_exists($this->user, 'getUserIdentifier')) {
            return $this->user->getUserIdentifier();
        }

        return $this->user->getUsername();
    }

    public function isAuthenticated()
    {
        return true;
    }

    /**
     * @param bool $isAuthenticated
     *
     * @return void
     * @phpstan-return never
     */
    public function setAuthenticated($isAuthenticated)
    {
        throw new \BadMethodCallException(sprintf('Cannot change authentication state of %s.', self::class));
    }

    public function eraseCredentials(): void
    {
    }

    public function getAttributes(): array
    {
        return [];
    }

    public function setAttributes(array $attributes): void
    {
        throw new \BadMethodCallException(sprintf('Cannot set attributes of %s.', self::class));
    }

    public function hasAttribute(string $name): bool
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function getAttribute(string $name)
    {
        return null;
    }

    public function setAttribute(string $name, $value): void
    {
        throw new \BadMethodCallException(sprintf('Cannot add attribute to %s.', self::class));
    }

    public function __serialize(): array
    {
        return [$this->user];
    }

    public function __unserialize(array $data): void
    {
        [$this->user] = $data;
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize($serialized): void
    {
        $this->__unserialize(unserialize($serialized));
    }
}
