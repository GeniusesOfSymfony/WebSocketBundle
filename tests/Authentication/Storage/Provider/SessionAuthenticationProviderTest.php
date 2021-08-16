<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Authentication\Storage\Provider;

use Gos\Bundle\WebSocketBundle\Authentication\Provider\SessionAuthenticationProvider;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class SessionAuthenticationProviderTest extends TestCase
{
    private const FIREWALLS = ['main'];

    /**
     * @var MockObject&TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var SessionAuthenticationProvider
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->provider = new SessionAuthenticationProvider($this->tokenStorage, self::FIREWALLS);
    }

    public function testTheProviderSupportsAConnectionWhenItHasASession(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->Session = $this->createMock(SessionInterface::class);

        self::assertTrue($this->provider->supports($connection));
    }

    public function testTheProviderDoesNotSupportAConnectionWhenItDoesNotHaveASession(): void
    {
        self::assertFalse($this->provider->supports($this->createMock(ConnectionInterface::class)));
    }

    public function testATokenIsCreatedAndAddedToStorageWhenAGuestUserWithoutASessionConnects(): void
    {
        /** @var MockObject&SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('get')
            ->with('_security_main')
            ->willReturn(false);

        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->Session = $session;
        $connection->WAMP = (object) [
            'sessionId' => 'test',
        ];

        $storageIdentifier = '42';

        $this->tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->willReturn($storageIdentifier);

        $this->tokenStorage->expects(self::once())
            ->method('addToken')
            ->with($storageIdentifier, self::isInstanceOf(TokenInterface::class));

        self::assertInstanceOf(class_exists(NullToken::class) ? NullToken::class : AnonymousToken::class, $this->provider->authenticate($connection));
    }

    public function testAnAuthenticatedUserFromASharedSessionIsAuthenticated(): void
    {
        $token = new UsernamePasswordToken('user', 'password', 'main', ['ROLE_USER']);

        /** @var MockObject&SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('get')
            ->with('_security_main')
            ->willReturn(serialize($token));

        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->Session = $session;
        $connection->WAMP = (object) [
            'sessionId' => 'test',
        ];

        $storageIdentifier = '42';

        $this->tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->willReturn($storageIdentifier);

        $this->tokenStorage->expects(self::once())
            ->method('addToken')
            ->with($storageIdentifier, self::isInstanceOf(TokenInterface::class));

        self::assertEquals($token, $this->provider->authenticate($connection));
    }
}
