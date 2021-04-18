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
use Gos\Bundle\WebSocketBundle\EventListener\WebsocketClientEventSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

final class WebsocketClientEventSubscriberTest extends TestCase
{
    /**
     * @var MockObject&ClientStorageInterface
     */
    private $clientStorage;

    /**
     * @var MockObject&WebsocketAuthenticationProviderInterface
     */
    private $authenticationProvider;

    /**
     * @var TestLogger
     */
    private $logger;

    /**
     * @var WebsocketClientEventSubscriber
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientStorage = $this->createMock(ClientStorageInterface::class);
        $this->authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->logger = new TestLogger();

        $this->listener = new WebsocketClientEventSubscriber($this->clientStorage, $this->authenticationProvider);
        $this->listener->setLogger($this->logger);
    }

    public function testTheUserIsAuthenticatedWhenTheClientConnectEventIsDispatched(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        $this->authenticationProvider->expects($this->once())
            ->method('authenticate')
            ->with($connection)
            ->willReturn($this->createMock(AbstractToken::class));

        $this->listener->onClientConnect(new ClientConnectedEvent($connection));
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
        $token->expects($this->once())
            ->method(method_exists(AbstractToken::class, 'getUserIdentifier') ? 'getUserIdentifier' : 'getUsername')
            ->willReturn('username');

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

        $this->listener->onClientDisconnect(new ClientDisconnectedEvent($connection));
    }

    /**
     * @testdox A `ClientNotFoundException` is handled when attempting to remove the user from storage, this simulates a failure if the client is removed between the `hasClient` and `getClient` calls
     */
    public function testTheClientNotFoundExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorage(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

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

        $this->listener->onClientDisconnect(new ClientDisconnectedEvent($connection));

        $this->assertTrue($this->logger->hasInfoThatContains('User timed out'));
    }

    public function testTheStorageExceptionIsHandledWhenAttemptingToRemoveTheUserFromStorage(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

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

        $this->listener->onClientDisconnect(new ClientDisconnectedEvent($connection));

        $this->assertTrue($this->logger->hasInfoThatContains('Error processing user in storage'));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientErrorEvent(): void
    {
        (new WebsocketClientEventSubscriber($this->clientStorage, $this->authenticationProvider))
            ->onClientError(new ClientErrorEvent(new \Exception('Testing'), $this->createMock(ConnectionInterface::class)));
    }

    public function testTheClientErrorIsLogged(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';
        $connection->WAMP = (object) [
            'sessionId' => 'session',
        ];

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
            ->willReturn($this->createMock(AbstractToken::class));

        $this->clientStorage->expects($this->never())
            ->method('removeClient');

        $this->listener->onClientError(new ClientErrorEvent(new \Exception('Testing'), $connection));

        $this->assertTrue($this->logger->hasErrorThatContains('Connection error'));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testThereIsNoActionWhenNoLoggerIsSetOnTheClientRejectedEvent(): void
    {
        (new WebsocketClientEventSubscriber($this->clientStorage, $this->authenticationProvider))
            ->onClientRejected(new ClientRejectedEvent('localhost', null));
    }

    public function testTheClientRejectionIsLogged(): void
    {
        $this->listener->onClientRejected(new ClientRejectedEvent('localhost', null));

        $this->assertTrue($this->logger->hasWarningThatContains('Client rejected, bad origin'));
    }
}
