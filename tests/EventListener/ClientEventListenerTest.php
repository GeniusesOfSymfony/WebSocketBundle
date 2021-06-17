<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent;
use Gos\Bundle\WebSocketBundle\EventListener\ClientEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class ClientEventListenerTest extends TestCase
{
    /**
     * @var MockObject|ClientStorageInterface
     */
    private $clientStorage;

    /**
     * @var MockObject|WebsocketAuthenticationProviderInterface
     */
    private $authenticationProvider;

    /**
     * @var TestLogger
     */
    private $logger;

    /**
     * @var ClientEventListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientStorage = $this->createMock(ClientStorageInterface::class);
        $this->authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->logger = new TestLogger();

        $this->listener = new ClientEventListener($this->clientStorage, $this->authenticationProvider);
        $this->listener->setLogger($this->logger);
    }

    public function testTheUserIsAuthenticatedWhenTheClientConnectEventIsDispatched(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $event = new ClientConnectedEvent($connection);

        $this->authenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($connection)
            ->willReturn($this->createMock(AbstractToken::class));

        $this->listener->onClientConnect($event);
    }

    public function testTheUserIsRemovedFromStorageWhenTheClientDisconnectEventIsDispatched(): void
    {
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

        $this->clientStorage->expects(self::once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects(self::once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->clientStorage->expects(self::once())
            ->method('getClient')
            ->with($connection->resourceId)
            ->willReturn($token);

        $this->clientStorage->expects(self::once())
            ->method('removeClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->listener->onClientDisconnect($event);
    }

    /**
     * @testdox A `ClientNotFoundException` is handled when attempting to remove the user from storage, this simulates a failure if the client is removed between the `hasClient` and `getClient` calls
     */
    public function testTheClientNotFoundExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorage(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $event = new ClientDisconnectedEvent($connection);

        $this->clientStorage->expects(self::once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects(self::once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->clientStorage->expects(self::once())
            ->method('getClient')
            ->with($connection->resourceId)
            ->willThrowException(new ClientNotFoundException('Client not found'));

        $this->clientStorage->expects(self::never())
            ->method('removeClient');

        $this->listener->onClientDisconnect($event);

        self::assertTrue($this->logger->hasInfoThatContains('User timed out'));
    }

    public function testTheStorageExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorage(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $event = new ClientDisconnectedEvent($connection);

        $this->clientStorage->expects(self::once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects(self::once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willThrowException(new StorageException('Driver failure'));

        $this->clientStorage->expects(self::never())
            ->method('removeClient');

        $this->listener->onClientDisconnect($event);

        self::assertTrue($this->logger->hasInfoThatContains('Error processing user in storage'));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientErrorEvent(): void
    {
        $event = new ClientErrorEvent($this->createMock(ConnectionInterface::class));

        (new ClientEventListener($this->clientStorage, $this->authenticationProvider))->onClientError($event);
    }

    public function testTheClientErrorIsLogged(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $event = new ClientErrorEvent($connection);
        $event->setException(new \Exception('Testing'));

        $this->clientStorage->expects(self::once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects(self::once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->clientStorage->expects(self::once())
            ->method('getClient')
            ->with($connection->resourceId)
            ->willReturn($this->createMock(AbstractToken::class));

        $this->clientStorage->expects(self::never())
            ->method('removeClient');

        $this->listener->onClientError($event);

        self::assertTrue($this->logger->hasErrorThatContains('Connection error'));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientRejectedEvent(): void
    {
        $event = new ClientRejectedEvent('localhost', null);

        $this->listener->onClientRejected($event);
    }

    public function testTheClientRejectionIsLogged(): void
    {
        $event = new ClientRejectedEvent('localhost', null);

        $this->listener->onClientRejected($event);

        self::assertTrue($this->logger->hasWarningThatContains('Client rejected, bad origin'));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testThereIsNoActionWhenNoLoggerIsSetOnTheConnectionRejectedEvent(): void
    {
        $event = new ConnectionRejectedEvent($this->createMock(ConnectionInterface::class), null);

        $this->listener->onConnectionRejected($event);
    }

    public function testTheConnectionRejectionIsLogged(): void
    {
        $event = new ConnectionRejectedEvent($this->createMock(ConnectionInterface::class), null);

        $this->listener->onConnectionRejected($event);

        self::assertTrue($this->logger->hasWarningThatContains('Connection rejected'));
    }
}
