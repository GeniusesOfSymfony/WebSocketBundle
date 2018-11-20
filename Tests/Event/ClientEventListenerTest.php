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
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * @var ClientEventListener
     */
    private $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->clientStorage = $this->createMock(ClientStorageInterface::class);
        $this->authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->listener = new ClientEventListener($this->clientStorage, $this->authenticationProvider);
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

        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUsername')
            ->willReturn('username');

        $event = $this->createMock(ClientEvent::class);
        $event->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->with($connection->WAMP->clientStorageId)
            ->willReturn($user);

        $this->clientStorage->expects($this->once())
            ->method('removeClient')
            ->with($connection->resourceId)
            ->willReturn(true);

        $this->listener->onClientDisconnect($event);
    }

    public function testTheClientNotFoundExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorage()
    {
        $logHandler = new TestHandler();

        $logger = new Logger(
            'websocket',
            [
                $logHandler
            ]
        );

        $this->listener->setLogger($logger);

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

        $this->assertTrue($logHandler->hasInfoThatContains('User timed out'));
    }

    public function testTheStorageExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorage()
    {
        $logHandler = new TestHandler();

        $logger = new Logger(
            'websocket',
            [
                $logHandler
            ]
        );

        $this->listener->setLogger($logger);

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

        $this->assertTrue($logHandler->hasInfoThatContains('Error processing user in storage'));
    }

    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientErrorEvent()
    {
        $event = $this->createMock(ClientErrorEvent::class);
        $event->expects($this->never())
            ->method('getConnection');

        $this->listener->onClientError($event);
    }

    public function testTheClientErrorIsLogged()
    {
        $logHandler = new TestHandler();

        $logger = new Logger(
            'websocket',
            [
                $logHandler
            ]
        );

        $this->listener->setLogger($logger);

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
            ->willReturn($this->createMock(UserInterface::class));

        $this->clientStorage->expects($this->never())
            ->method('removeClient');

        $this->listener->onClientError($event);

        $this->assertTrue($logHandler->hasErrorThatContains('Connection error'));
    }

    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientRejectedEvent()
    {
        $event = $this->createMock(ClientRejectedEvent::class);
        $event->expects($this->never())
            ->method('getOrigin');

        $this->listener->onClientRejected($event);
    }

    public function testTheClientRejectionIsLogged()
    {
        $logHandler = new TestHandler();

        $logger = new Logger(
            'websocket',
            [
                $logHandler
            ]
        );

        $this->listener->setLogger($logger);

        $event = $this->createMock(ClientRejectedEvent::class);
        $event->expects($this->once())
            ->method('getOrigin')
            ->willReturn('localhost');

        $this->listener->onClientRejected($event);

        $this->assertTrue($logHandler->hasWarningThatContains('Client rejected, bad origin'));
    }
}
