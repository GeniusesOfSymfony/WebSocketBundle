<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\Authentication\AuthenticatorInterface;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface;
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
     * @var MockObject&TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var MockObject&AuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var WebsocketClientEventSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->authenticator = $this->createMock(AuthenticatorInterface::class);

        $this->subscriber = new WebsocketClientEventSubscriber($this->tokenStorage, $this->authenticator);
    }

    public function testTheUserIsAuthenticatedWhenTheClientConnectEventIsDispatched(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        $this->authenticator->expects(self::once())
            ->method('authenticate')
            ->with($connection);

        $this->subscriber->onClientConnect(new ClientConnectedEvent($connection));
    }

    public function testTheUserIsRemovedFromStorageWhenTheClientDisconnectEventIsDispatched(): void
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

        $this->tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->tokenStorage->expects(self::once())
            ->method('hasToken')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->with($connection->resourceId)
            ->willReturn($token);

        $this->tokenStorage->expects(self::once())
            ->method('removeToken')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->subscriber->onClientDisconnect(new ClientDisconnectedEvent($connection));
    }

    /**
     * @testdox A `TokenNotFoundException` is handled when attempting to remove the user from storage, this simulates a failure if the client is removed between the `hasClient` and `getClient` calls
     */
    public function testTheTokenNotFoundExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorage(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $this->tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->tokenStorage->expects(self::once())
            ->method('hasToken')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->with($connection->resourceId)
            ->willThrowException(new TokenNotFoundException('Client not found'));

        $this->tokenStorage->expects(self::never())
            ->method('removeToken');

        $this->subscriber->onClientDisconnect(new ClientDisconnectedEvent($connection));
    }

    public function testTheStorageExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorage(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $this->tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->tokenStorage->expects(self::once())
            ->method('hasToken')
            ->with($connection->resourceId)
            ->willThrowException(new StorageException('Driver failure'));

        $this->tokenStorage->expects(self::never())
            ->method('removeToken');

        $this->subscriber->onClientDisconnect(new ClientDisconnectedEvent($connection));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientErrorEvent(): void
    {
        $this->subscriber->onClientError(
            new ClientErrorEvent(new \Exception('Testing'), $this->createMock(ConnectionInterface::class))
        );
    }

    /**
     * @group legacy
     */
    public function testTheClientErrorIsLogged(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $this->tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->tokenStorage->expects(self::once())
            ->method('hasToken')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->with($connection->resourceId)
            ->willReturn($this->createMock(AbstractToken::class));

        $this->tokenStorage->expects(self::never())
            ->method('removeToken');

        $this->subscriber->setLogger(new NullLogger());
        $this->subscriber->onClientError(new ClientErrorEvent(new \Exception('Testing'), $connection));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testThereIsNoActionWhenNoLoggerIsSetOnTheConnectionRejectedEvent(): void
    {
        $this->subscriber->onConnectionRejected(
            new ConnectionRejectedEvent($this->createMock(ConnectionInterface::class), null)
        );
    }
}
