<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client\Auth;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProvider;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @group legacy
 */
final class WebsocketAuthenticationProviderTest extends TestCase
{
    private const FIREWALLS = ['main'];

    /**
     * @var MockObject&ClientStorageInterface
     */
    private $clientStorage;

    /**
     * @var WebsocketAuthenticationProvider
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientStorage = $this->createMock(ClientStorageInterface::class);

        $this->provider = new WebsocketAuthenticationProvider($this->clientStorage, self::FIREWALLS);
    }

    public function testAnAnonymousTokenIsCreatedAndAddedToStorageWhenAGuestUserConnects(): void
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

        $clientIdentifier = 42;

        $this->clientStorage->expects(self::once())
            ->method('getStorageId')
            ->willReturn((string) $clientIdentifier);

        $this->clientStorage->expects(self::once())
            ->method('addClient');

        self::assertInstanceOf(AnonymousToken::class, $this->provider->authenticate($connection));
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

        $clientIdentifier = 42;

        $this->clientStorage->expects(self::once())
            ->method('getStorageId')
            ->willReturn((string) $clientIdentifier);

        $this->clientStorage->expects(self::once())
            ->method('addClient');

        self::assertEquals($token, $this->provider->authenticate($connection));
    }
}
