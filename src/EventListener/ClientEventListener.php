<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

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
use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class ClientEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ClientStorageInterface|TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var WebsocketAuthenticationProviderInterface|AuthenticatorInterface
     */
    private $authenticator;

    /**
     * @param ClientStorageInterface|TokenStorageInterface                    $tokenStorage
     * @param WebsocketAuthenticationProviderInterface|AuthenticatorInterface $authenticator
     *
     * @throws \InvalidArgumentException if invalid parameters are given
     */
    public function __construct(object $tokenStorage, object $authenticator)
    {
        if (!($tokenStorage instanceof ClientStorageInterface) && !($tokenStorage instanceof TokenStorageInterface)) {
            throw new \InvalidArgumentException(sprintf('Argument 1 of the %s constructor must be an instance of %s or %s, "%s" given.', self::class, ClientStorageInterface::class, TokenStorageInterface::class, \get_class($tokenStorage)));
        }

        if (!($authenticator instanceof WebsocketAuthenticationProviderInterface) && !($authenticator instanceof AuthenticatorInterface)) {
            throw new \InvalidArgumentException(sprintf('Argument 2 of the %s constructor must be an instance of %s or %s, "%s" given.', self::class, WebsocketAuthenticationProviderInterface::class, AuthenticatorInterface::class, \get_class($tokenStorage)));
        }

        // The arguments must both be part of the Client API or Authentication API, cannot be one of each
        if (($tokenStorage instanceof ClientStorageInterface && $authenticator instanceof AuthenticatorInterface) || ($tokenStorage instanceof TokenStorageInterface && $authenticator instanceof WebsocketAuthenticationProviderInterface)) {
            throw new \InvalidArgumentException(sprintf('Cannot use mixed APIs in %s.', self::class));
        }

        $this->tokenStorage = $tokenStorage;
        $this->authenticator = $authenticator;
    }

    public function onClientConnect(ClientConnectedEvent $event): void
    {
        $this->authenticator->authenticate($event->getConnection());
    }

    public function onClientDisconnect(ClientDisconnectedEvent $event): void
    {
        $connection = $event->getConnection();

        $storageId = $this->generateStorageId($connection);

        $loggerContext = [
            'connection_id' => $connection->resourceId,
            'session_id' => $connection->WAMP->sessionId,
            'storage_id' => $storageId,
        ];

        try {
            if ($this->hasTokenInStorage($storageId)) {
                $token = $this->getTokenFromStorage($storageId);

                $this->removeTokenInStorage($storageId);

                $userIdentifier = method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername();

                if (null !== $this->logger) {
                    $this->logger->info(
                        sprintf('%s disconnected', $userIdentifier),
                        array_merge(
                            $loggerContext,
                            ['user_identifier' => $userIdentifier]
                        )
                    );
                }
            }
        } catch (ClientNotFoundException | TokenNotFoundException $e) {
            if (null !== $this->logger) {
                $this->logger->info(
                    'User timed out',
                    array_merge(
                        $loggerContext,
                        ['exception' => $e]
                    )
                );
            }
        } catch (LegacyStorageException | StorageException $e) {
            if (null !== $this->logger) {
                $this->logger->info(
                    'Error processing user in storage',
                    array_merge(
                        $loggerContext,
                        ['exception' => $e]
                    )
                );
            }
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

        $storageId = $this->generateStorageId($connection);

        if ($this->hasTokenInStorage($storageId)) {
            $token = $this->getTokenFromStorage($storageId);

            $loggerContext['user_identifier'] = method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername();
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
        if (null !== $this->logger) {
            $this->logger->warning(
                'Client rejected, bad origin',
                [
                    'origin' => $event->getOrigin(),
                ]
            );
        }
    }

    public function onConnectionRejected(ConnectionRejectedEvent $event): void
    {
        if (null !== $this->logger) {
            $this->logger->warning('Connection rejected');
        }
    }

    private function generateStorageId(ConnectionInterface $connection): string
    {
        if ($this->tokenStorage instanceof TokenStorageInterface) {
            return $this->tokenStorage->generateStorageId($connection);
        }

        return $this->tokenStorage->getStorageId($connection);
    }

    private function getTokenFromStorage(string $id): TokenInterface
    {
        if ($this->tokenStorage instanceof TokenStorageInterface) {
            return $this->tokenStorage->getToken($id);
        }

        return $this->tokenStorage->getClient($id);
    }

    private function hasTokenInStorage(string $id): bool
    {
        if ($this->tokenStorage instanceof TokenStorageInterface) {
            return $this->tokenStorage->hasToken($id);
        }

        return $this->tokenStorage->hasClient($id);
    }

    private function removeTokenInStorage(string $id): bool
    {
        if ($this->tokenStorage instanceof TokenStorageInterface) {
            return $this->tokenStorage->removeToken($id);
        }

        return $this->tokenStorage->removeClient($id);
    }
}
