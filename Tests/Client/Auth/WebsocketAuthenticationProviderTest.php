<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Client\Auth;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProvider;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class WebsocketAuthenticationProviderTest extends TestCase
{
    private const FIREWALLS = ['main'];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClientStorageInterface
     */
    private $clientStorage;

    /**
     * @var WebsocketAuthenticationProvider
     */
    private $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->clientStorage = $this->createMock(ClientStorageInterface::class);

        $this->provider = new WebsocketAuthenticationProvider(
            $this->tokenStorage, self::FIREWALLS, $this->clientStorage
        );
    }

    public function testAnAnonymousTokenIsCreatedAndAddedToStorageWhenAGuestUserConnects()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('get')
            ->with('_security_main')
            ->willReturn(false);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->Session = $session;
        $connection->WAMP = (object) [
            'sessionId' => 'test',
        ];

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->tokenStorage->expects($this->once())
            ->method('setToken');

        $clientIdentifier = 42;

        $this->clientStorage->expects($this->once())
            ->method('getStorageId')
            ->willReturn($clientIdentifier);

        $this->clientStorage->expects($this->once())
            ->method('addClient');

        $this->assertInstanceOf(AnonymousToken::class, $this->provider->authenticate($connection));
    }

    public function testAnAuthenticatedUserFromASharedSessionIsAuthenticated()
    {
        $token = new UsernamePasswordToken('user', 'password', 'main', ['ROLE_USER']);

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('get')
            ->with('_security_main')
            ->willReturn(serialize($token));

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->Session = $session;
        $connection->WAMP = (object) [
            'sessionId' => 'test',
        ];

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->tokenStorage->expects($this->once())
            ->method('setToken');

        $clientIdentifier = 42;

        $this->clientStorage->expects($this->once())
            ->method('getStorageId')
            ->willReturn($clientIdentifier);

        $this->clientStorage->expects($this->once())
            ->method('addClient');

        $this->assertEquals($token, $this->provider->authenticate($connection));
    }
}
