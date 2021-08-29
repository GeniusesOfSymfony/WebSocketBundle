<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\Authentication\AuthenticatorInterface;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException as LegacyStorageException;
use Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent;
use Gos\Bundle\WebSocketBundle\EventListener\WebsocketClientEventSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

final class WebsocketClientEventSubscriberTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testTheListenerRejectsMixedApiArguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Cannot use mixed APIs in %s.', WebsocketClientEventSubscriber::class));

        /** @var MockObject&ClientStorageInterface $clientStorage */
        $clientStorage = $this->createMock(ClientStorageInterface::class);

        /** @var MockObject&AuthenticatorInterface $authenticator */
        $authenticator = $this->createMock(AuthenticatorInterface::class);

        $this->createListener($clientStorage, $authenticator);
    }

    /**
     * @group legacy
     */
    public function testTheUserIsAuthenticatedWhenTheClientConnectEventIsDispatchedWithTheClientApi(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        $event = new ClientConnectedEvent($connection);

        /** @var MockObject&ClientStorageInterface $clientStorage */
        $clientStorage = $this->createMock(ClientStorageInterface::class);

        /** @var MockObject&WebsocketAuthenticationProviderInterface $authenticationProvider */
        $authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);
        $authenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($connection)
            ->willReturn($this->createMock(AbstractToken::class));

        $this->createListener($clientStorage, $authenticationProvider)->onClientConnect($event);
    }

    public function testTheUserIsAuthenticatedWhenTheClientConnectEventIsDispatchedUsingTheAuthenticationApi(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        $event = new ClientConnectedEvent($connection);

        /** @var MockObject&TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        /** @var MockObject&AuthenticatorInterface $authenticator */
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $authenticator->expects(self::once())
            ->method('authenticate')
            ->with($connection);

        $this->createListener($tokenStorage, $authenticator)->onClientConnect($event);
    }

    /**
     * @group legacy
     */
    public function testTheUserIsRemovedFromStorageWhenTheClientDisconnectEventIsDispatchedWithTheClientApi(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        /** @var MockObject&AbstractToken $token */
        $token = $this->createMock(AbstractToken::class);
        $token->expects(self::once())
            ->method(method_exists(AbstractToken::class, 'getUserIdentifier') ? 'getUserIdentifier' : 'getUsername')
            ->willReturn('username');

        $event = new ClientDisconnectedEvent($connection);

        /** @var MockObject&ClientStorageInterface $clientStorage */
        $clientStorage = $this->createMock(ClientStorageInterface::class);
        $clientStorage->expects(self::once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $clientStorage->expects(self::once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $clientStorage->expects(self::once())
            ->method('getClient')
            ->with($connection->resourceId)
            ->willReturn($token);

        $clientStorage->expects(self::once())
            ->method('removeClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        /** @var MockObject&WebsocketAuthenticationProviderInterface $authenticationProvider */
        $authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->createListener($clientStorage, $authenticationProvider)->onClientDisconnect($event);
    }

    public function testTheUserIsRemovedFromStorageWhenTheClientDisconnectEventIsDispatchedWithTheAuthenticationApi(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        /** @var MockObject&AbstractToken $token */
        $token = $this->createMock(AbstractToken::class);
        $token->expects(self::once())
            ->method(method_exists(AbstractToken::class, 'getUserIdentifier') ? 'getUserIdentifier' : 'getUsername')
            ->willReturn('username');

        $event = new ClientDisconnectedEvent($connection);

        /** @var MockObject&TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $tokenStorage->expects(self::once())
            ->method('hasToken')
            ->with($connection->resourceId)
            ->willReturn(true);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->with($connection->resourceId)
            ->willReturn($token);

        $tokenStorage->expects(self::once())
            ->method('removeToken')
            ->with($connection->resourceId)
            ->willReturn(true);

        /** @var MockObject&AuthenticatorInterface $authenticator */
        $authenticator = $this->createMock(AuthenticatorInterface::class);

        $this->createListener($tokenStorage, $authenticator)->onClientDisconnect($event);
    }

    /**
     * @group legacy
     *
     * @testdox A `ClientNotFoundException` is handled when attempting to remove the user from storage when using the Client API, this simulates a failure if the client is removed between the `hasClient` and `getClient` calls
     */
    public function testTheClientNotFoundExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorageWithTheClientApi(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $event = new ClientDisconnectedEvent($connection);

        /** @var MockObject&ClientStorageInterface $clientStorage */
        $clientStorage = $this->createMock(ClientStorageInterface::class);
        $clientStorage->expects(self::once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $clientStorage->expects(self::once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $clientStorage->expects(self::once())
            ->method('getClient')
            ->with($connection->resourceId)
            ->willThrowException(new ClientNotFoundException('Client not found'));

        $clientStorage->expects(self::never())
            ->method('removeClient');

        /** @var MockObject&WebsocketAuthenticationProviderInterface $authenticationProvider */
        $authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->createListener($clientStorage, $authenticationProvider)->onClientDisconnect($event);
    }

    /**
     * @testdox A `ClientNotFoundException` is handled when attempting to remove the user from storage when using the Authentication API, this simulates a failure if the client is removed between the `hasClient` and `getClient` calls
     */
    public function testTheClientNotFoundExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorageWithTheAuthenticationApi(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $event = new ClientDisconnectedEvent($connection);

        /** @var MockObject&TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $tokenStorage->expects(self::once())
            ->method('hasToken')
            ->with($connection->resourceId)
            ->willReturn(true);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->with($connection->resourceId)
            ->willThrowException(new TokenNotFoundException('Client not found'));

        $tokenStorage->expects(self::never())
            ->method('removeToken');

        /** @var MockObject&AuthenticatorInterface $authenticator */
        $authenticator = $this->createMock(AuthenticatorInterface::class);

        $this->createListener($tokenStorage, $authenticator)->onClientDisconnect($event);
    }

    /**
     * @group legacy
     */
    public function testTheStorageExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorageWithTheClientApi(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $event = new ClientDisconnectedEvent($connection);

        /** @var MockObject&ClientStorageInterface $clientStorage */
        $clientStorage = $this->createMock(ClientStorageInterface::class);
        $clientStorage->expects(self::once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $clientStorage->expects(self::once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willThrowException(new LegacyStorageException('Driver failure'));

        $clientStorage->expects(self::never())
            ->method('removeClient');

        /** @var MockObject&WebsocketAuthenticationProviderInterface $authenticationProvider */
        $authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->createListener($clientStorage, $authenticationProvider)->onClientDisconnect($event);
    }

    public function testTheStorageExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorageWithTheAuthenticationApi(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $event = new ClientDisconnectedEvent($connection);

        /** @var MockObject&TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $tokenStorage->expects(self::once())
            ->method('hasToken')
            ->with($connection->resourceId)
            ->willThrowException(new StorageException('Driver failure'));

        $tokenStorage->expects(self::never())
            ->method('removeToken');

        /** @var MockObject&AuthenticatorInterface $authenticator */
        $authenticator = $this->createMock(AuthenticatorInterface::class);

        $this->createListener($tokenStorage, $authenticator)->onClientDisconnect($event);
    }

    /**
     * @group legacy
     * @doesNotPerformAssertions
     */
    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientErrorEventWithTheClientApi(): void
    {
        $event = new ClientErrorEvent(new \Exception('Testing'), $this->createMock(ConnectionInterface::class));

        /** @var MockObject&ClientStorageInterface $clientStorage */
        $clientStorage = $this->createMock(ClientStorageInterface::class);

        /** @var MockObject&WebsocketAuthenticationProviderInterface $authenticationProvider */
        $authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->createListener($clientStorage, $authenticationProvider)->onClientError($event);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientErrorEventWithTheAuthenticationApi(): void
    {
        $event = new ClientErrorEvent(new \Exception('Testing'), $this->createMock(ConnectionInterface::class));

        /** @var MockObject&TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        /** @var MockObject&AuthenticatorInterface $authenticator */
        $authenticator = $this->createMock(AuthenticatorInterface::class);

        $this->createListener($tokenStorage, $authenticator)->onClientError($event);
    }

    /**
     * @group legacy
     */
    public function testTheClientErrorIsLoggedWithTheClientApi(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $event = new ClientErrorEvent(new \Exception('Testing'), $connection);

        /** @var MockObject&ClientStorageInterface $clientStorage */
        $clientStorage = $this->createMock(ClientStorageInterface::class);
        $clientStorage->expects(self::once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $clientStorage->expects(self::once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $clientStorage->expects(self::once())
            ->method('getClient')
            ->with($connection->resourceId)
            ->willReturn($this->createMock(AbstractToken::class));

        $clientStorage->expects(self::never())
            ->method('removeClient');

        /** @var MockObject&WebsocketAuthenticationProviderInterface $authenticationProvider */
        $authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $listener = $this->createListener($clientStorage, $authenticationProvider);
        $listener->setLogger(new NullLogger());
        $listener->onClientError($event);
    }

    /**
     * @group legacy
     */
    public function testTheClientErrorIsLoggedWithTheAuthenticationApi(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $event = new ClientErrorEvent(new \Exception('Testing'), $connection);

        /** @var MockObject&TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $tokenStorage->expects(self::once())
            ->method('hasToken')
            ->with($connection->resourceId)
            ->willReturn(true);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->with($connection->resourceId)
            ->willReturn($this->createMock(AbstractToken::class));

        $tokenStorage->expects(self::never())
            ->method('removeToken');

        /** @var MockObject&AuthenticatorInterface $authenticator */
        $authenticator = $this->createMock(AuthenticatorInterface::class);

        $listener = $this->createListener($tokenStorage, $authenticator);
        $listener->setLogger(new NullLogger());
        $listener->onClientError($event);
    }

    /**
     * @group legacy
     * @doesNotPerformAssertions
     */
    public function testThereIsNoActionWhenNoLoggerIsSetOnTheConnectionRejectedEventWithTheClientApi(): void
    {
        $event = new ConnectionRejectedEvent($this->createMock(ConnectionInterface::class), null);

        /** @var MockObject&ClientStorageInterface $clientStorage */
        $clientStorage = $this->createMock(ClientStorageInterface::class);

        /** @var MockObject&WebsocketAuthenticationProviderInterface $authenticationProvider */
        $authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->createListener($clientStorage, $authenticationProvider)->onConnectionRejected($event);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testThereIsNoActionWhenNoLoggerIsSetOnTheConnectionRejectedEventWithTheAuthenticationApi(): void
    {
        $event = new ConnectionRejectedEvent($this->createMock(ConnectionInterface::class), null);

        /** @var MockObject&TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        /** @var MockObject&AuthenticatorInterface $authenticator */
        $authenticator = $this->createMock(AuthenticatorInterface::class);

        $this->createListener($tokenStorage, $authenticator)->onConnectionRejected($event);
    }

    /**
     * @param ClientStorageInterface|TokenStorageInterface                    $tokenStorage
     * @param WebsocketAuthenticationProviderInterface|AuthenticatorInterface $authenticator
     *
     * @throws \InvalidArgumentException if invalid parameters are given
     */
    private function createListener(object $tokenStorage, object $authenticator): WebsocketClientEventSubscriber
    {
        return new WebsocketClientEventSubscriber($tokenStorage, $authenticator);
    }
}
