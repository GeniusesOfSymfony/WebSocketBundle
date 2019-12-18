<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\EventListener\ClientEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

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
     * @var \Gos\Bundle\WebSocketBundle\EventListener\ClientEventListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientStorage = $this->createMock(ClientStorageInterface::class);
        $this->authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->logger = new TestLogger();

        $this->listener = new \Gos\Bundle\WebSocketBundle\EventListener\ClientEventListener($this->clientStorage, $this->authenticationProvider);
        $this->listener->setLogger($this->logger);
    }

    public function testTheUserIsAuthenticatedWhenTheClientConnectEventIsDispatched(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $event = $this->createMock(ClientEvent::class);
        $event->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->authenticationProvider->expects($this->once())
            ->method('authenticate')
            ->with($connection)
            ->willReturn($this->createMock(TokenInterface::class));

        $this->listener->onClientConnect($event);
    }

    public function testTheUserIsRemovedFromStorageWhenTheClientDisconnectEventIsDispatched(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUsername')
            ->willReturn('username');

        $event = $this->createMock(ClientEvent::class);
        $event->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->clientStorage->expects($this->once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects($this->once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->with($connection->resourceId)
            ->willReturn($token);

        $this->clientStorage->expects($this->once())
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

        $event = $this->createMock(ClientEvent::class);
        $event->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->clientStorage->expects($this->once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects($this->once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->with($connection->resourceId)
            ->willThrowException(new ClientNotFoundException('Client not found'));

        $this->clientStorage->expects($this->never())
            ->method('removeClient');

        $this->listener->onClientDisconnect($event);

        $this->assertTrue($this->logger->hasInfoThatContains('User timed out'));
    }

    public function testTheStorageExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorage(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $event = $this->createMock(ClientEvent::class);
        $event->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->clientStorage->expects($this->once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects($this->once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willThrowException(new StorageException('Driver failure'));

        $this->clientStorage->expects($this->never())
            ->method('removeClient');

        $this->listener->onClientDisconnect($event);

        $this->assertTrue($this->logger->hasInfoThatContains('Error processing user in storage'));
    }

    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientErrorEvent(): void
    {
        $event = $this->createMock(ClientErrorEvent::class);
        $event->expects($this->never())
            ->method('getConnection');

        (new ClientEventListener($this->clientStorage, $this->authenticationProvider))->onClientError($event);
    }

    public function testTheClientErrorIsLogged(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

        $event = $this->createMock(ClientErrorEvent::class);
        $event->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $event->expects($this->once())
            ->method('getException')
            ->willReturn(new \Exception('Testing'));

        $this->clientStorage->expects($this->once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects($this->once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->with($connection->resourceId)
            ->willReturn($this->createMock(TokenInterface::class));

        $this->clientStorage->expects($this->never())
            ->method('removeClient');

        $this->listener->onClientError($event);

        $this->assertTrue($this->logger->hasErrorThatContains('Connection error'));
    }

    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientRejectedEvent(): void
    {
        $event = $this->createMock(ClientRejectedEvent::class);
        $event->expects($this->never())
            ->method('getOrigin');

        (new ClientEventListener($this->clientStorage, $this->authenticationProvider))->onClientRejected($event);
    }

    public function testTheClientRejectionIsLogged(): void
    {
        $event = $this->createMock(ClientRejectedEvent::class);
        $event->expects($this->once())
            ->method('getOrigin')
            ->willReturn('localhost');

        $this->listener->onClientRejected($event);

        $this->assertTrue($this->logger->hasWarningThatContains('Client rejected, bad origin'));
    }
}
