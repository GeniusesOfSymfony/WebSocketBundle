<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Event;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEventListener;
use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ClientEventListenerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClientStorageInterface
     */
    private $clientStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WebsocketAuthenticationProviderInterface
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

    public function testTheUserIsAuthenticatedWhenTheClientConnectEventIsDispatched()
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

    public function testTheUserIsRemovedFromStorageWhenTheClientDisconnectEventIsDispatched()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
            'clientStorageId' => 'client_storage'
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
            ->method('getClient')
            ->with($connection->WAMP->clientStorageId)
            ->willReturn($token);

        $this->clientStorage->expects($this->once())
            ->method('removeClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->listener->onClientDisconnect($event);
    }

    public function testTheClientNotFoundExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorage()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
            'clientStorageId' => 'client_storage'
        ];

        $event = $this->createMock(ClientEvent::class);
        $event->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->with($connection->WAMP->clientStorageId)
            ->willThrowException(new ClientNotFoundException('Client not found'));

        $this->clientStorage->expects($this->never())
            ->method('removeClient');

        $this->listener->onClientDisconnect($event);

        $this->assertTrue($this->logger->hasInfoThatContains('User timed out'));
    }

    public function testTheStorageExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorage()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
            'clientStorageId' => 'client_storage'
        ];

        $event = $this->createMock(ClientEvent::class);
        $event->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->with($connection->WAMP->clientStorageId)
            ->willThrowException(new StorageException('Driver failure'));

        $this->clientStorage->expects($this->never())
            ->method('removeClient');

        $this->listener->onClientDisconnect($event);

        $this->assertTrue($this->logger->hasInfoThatContains('Error processing user in storage'));
    }

    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientErrorEvent()
    {
        $event = $this->createMock(ClientErrorEvent::class);
        $event->expects($this->never())
            ->method('getConnection');

        (new ClientEventListener($this->clientStorage, $this->authenticationProvider))->onClientError($event);
    }

    public function testTheClientErrorIsLogged()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
            'clientStorageId' => 'client_storage'
        ];

        $event = $this->createMock(ClientErrorEvent::class);
        $event->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $event->expects($this->once())
            ->method('getException')
            ->willReturn(new \Exception('Testing'));

        $this->clientStorage->expects($this->once())
            ->method('hasClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->with($connection->WAMP->clientStorageId)
            ->willReturn($this->createMock(TokenInterface::class));

        $this->clientStorage->expects($this->never())
            ->method('removeClient');

        $this->listener->onClientError($event);

        $this->assertTrue($this->logger->hasErrorThatContains('Connection error'));
    }

    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientRejectedEvent()
    {
        $event = $this->createMock(ClientRejectedEvent::class);
        $event->expects($this->never())
            ->method('getOrigin');

        (new ClientEventListener($this->clientStorage, $this->authenticationProvider))->onClientRejected($event);
    }

    public function testTheClientRejectionIsLogged()
    {
        $event = $this->createMock(ClientRejectedEvent::class);
        $event->expects($this->once())
            ->method('getOrigin')
            ->willReturn('localhost');

        $this->listener->onClientRejected($event);

        $this->assertTrue($this->logger->hasWarningThatContains('Client rejected, bad origin'));
    }
}
