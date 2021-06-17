<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final class WebsocketClientEventSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ClientStorageInterface $clientStorage;
    private WebsocketAuthenticationProviderInterface $authenticationProvider;

    public function __construct(
        ClientStorageInterface $clientStorage,
        WebsocketAuthenticationProviderInterface $authenticationProvider
    ) {
        $this->clientStorage = $clientStorage;
        $this->authenticationProvider = $authenticationProvider;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ClientConnectedEvent::class => 'onClientConnect',
            ClientDisconnectedEvent::class => 'onClientDisconnect',
            ClientErrorEvent::class => 'onClientError',
            ClientRejectedEvent::class => 'onClientRejected',
            ConnectionRejectedEvent::class => 'onConnectionRejected',
        ];
    }

    public function onClientConnect(ClientConnectedEvent $event): void
    {
        $this->authenticationProvider->authenticate($event->getConnection());
    }

    public function onClientDisconnect(ClientDisconnectedEvent $event): void
    {
        $conn = $event->getConnection();
        $storageId = $this->clientStorage->getStorageId($conn);

        $loggerContext = [
            'connection_id' => $conn->resourceId,
            'session_id' => $conn->WAMP->sessionId,
            'storage_id' => $storageId,
        ];

        try {
            if ($this->clientStorage->hasClient($storageId)) {
                $token = $this->clientStorage->getClient($storageId);

                $this->clientStorage->removeClient($storageId);

                $username = method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername();

                $this->logger?->info(
                    sprintf('%s disconnected', $username),
                    array_merge(
                        $loggerContext,
                        ['username' => $username]
                    )
                );
            }
        } catch (ClientNotFoundException $e) {
            $this->logger?->info(
                'User timed out',
                array_merge(
                    $loggerContext,
                    ['exception' => $e]
                )
            );
        } catch (StorageException $e) {
            $this->logger?->info(
                'Error processing user in storage',
                array_merge(
                    $loggerContext,
                    ['exception' => $e]
                )
            );
        }
    }

    public function onClientError(ClientErrorEvent $event): void
    {
        if (null === $this->logger) {
            return;
        }

        $conn = $event->getConnection();
        $throwable = $event->getThrowable();

        $loggerContext = [
            'connection_id' => $conn->resourceId,
            'session_id' => $conn->WAMP->sessionId,
            'exception' => $throwable,
        ];

        $storageId = $this->clientStorage->getStorageId($conn);

        if ($this->clientStorage->hasClient($storageId)) {
            $token = $this->clientStorage->getClient($storageId);

            $loggerContext['client'] = method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername();
        }

        $this->logger->error(
            'Connection error',
            $loggerContext
        );
    }

    /**
     * @deprecated to be removed in 4.0
     */
    public function onClientRejected(ClientRejectedEvent $event): void
    {
        $this->logger?->warning(
            'Client rejected, bad origin',
            [
                'origin' => $event->getOrigin(),
            ]
        );
    }

    public function onConnectionRejected(ConnectionRejectedEvent $event): void
    {
        $this->logger?->warning('Connection rejected');
    }
}
