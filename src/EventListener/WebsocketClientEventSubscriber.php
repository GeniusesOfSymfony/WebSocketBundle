<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Authentication\AuthenticatorInterface;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface;
use Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
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

    private TokenStorageInterface $tokenStorage;

    private AuthenticatorInterface $authenticator;

    public function __construct(TokenStorageInterface $tokenStorage, AuthenticatorInterface $authenticator)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authenticator = $authenticator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ClientConnectedEvent::class => 'onClientConnect',
            ClientDisconnectedEvent::class => 'onClientDisconnect',
            ClientErrorEvent::class => 'onClientError',
            ConnectionRejectedEvent::class => 'onConnectionRejected',
        ];
    }

    public function onClientConnect(ClientConnectedEvent $event): void
    {
        $this->authenticator->authenticate($event->getConnection());
    }

    public function onClientDisconnect(ClientDisconnectedEvent $event): void
    {
        $connection = $event->getConnection();

        $storageId = $this->tokenStorage->generateStorageId($connection);

        $loggerContext = [
            'connection_id' => $connection->resourceId,
            'session_id' => $connection->WAMP->sessionId,
            'storage_id' => $storageId,
        ];

        try {
            if ($this->tokenStorage->hasToken($storageId)) {
                $token = $this->tokenStorage->getToken($storageId);

                $this->tokenStorage->removeToken($storageId);

                $userIdentifier = method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername();

                $this->logger?->info(
                    sprintf('%s disconnected', $userIdentifier),
                    array_merge(
                        $loggerContext,
                        ['user_identifier' => $userIdentifier]
                    )
                );
            }
        } catch (TokenNotFoundException $e) {
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

        $connection = $event->getConnection();

        $loggerContext = [
            'connection_id' => $connection->resourceId,
            'session_id' => $connection->WAMP->sessionId,
            'exception' => $event->getThrowable(),
        ];

        $storageId = $this->tokenStorage->generateStorageId($connection);

        if ($this->tokenStorage->hasToken($storageId)) {
            $token = $this->tokenStorage->getToken($storageId);

            $loggerContext['user_identifier'] = method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername();
        }

        $this->logger->error(
            'Connection error',
            $loggerContext
        );
    }

    public function onConnectionRejected(ConnectionRejectedEvent $event): void
    {
        $this->logger?->warning('Connection rejected');
    }
}
