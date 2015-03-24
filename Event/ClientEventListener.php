<?php
namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Client\Authenticator\AuthenticatorInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\StorageException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class ClientEventListener
{
    /**
     * @param ClientStorageInterface $clientStorage
     */
    protected $clientStorage;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var AuthenticatorInterface
     */
    protected $authenticator;

    /**
     * @param ClientStorageInterface $clientStorage
     * @param AuthenticatorInterface $authenticator
     * @param LoggerInterface        $logger
     */
    public function __construct(
        ClientStorageInterface $clientStorage,
        AuthenticatorInterface $authenticator,
        LoggerInterface $logger = null
    ) {
        $this->clientStorage = $clientStorage;
        $this->authenticator = $authenticator;
        $this->logger = $logger;
    }

    /**
     * @param ClientEvent $event
     *
     * @throws StorageException
     * @throws \Exception
     */
    public function onClientConnect(ClientEvent $event)
    {
        $connection = $event->getConnection();

        if (null !== $this->logger) {
            $loggerContext = array(
                'connection_id' => $connection->resourceId,
                'session_id' => $connection->WAMP->sessionId,
            );
        }

        $sid = $this->clientStorage->getStorageId($connection);
        $loggerContext['storage_id'] = $sid;

        $user = $this->authenticator->authenticate($connection);
        $this->clientStorage->addClient($sid, $user);

        $username = $user instanceof UserInterface
            ? $user->getUsername()
            : $user;

        $connection->WAMP->clientStorageId = $sid;

        if (null !== $this->logger) {
            $this->logger->info(sprintf(
                '%s connected [%]',
                $username,
                $user instanceof UserInterface ? implode(', ', $user->getRoles()) : array()
            ), $loggerContext);
        }
    }

    /**
     * Called whenever a client disconnects.
     *
     * @param ClientEvent $event
     */
    public function onClientDisconnect(ClientEvent $event)
    {
        $connection = $event->getConnection();

        $sid = $this->clientStorage->getStorageId($connection);

        $this->clientStorage->removeClient($sid);

        if (null !== $this->logger) {
            $this->logger->info(sprintf(
                '%s disconnected',
                $connection->resourceId
            ), array(
                'connection_id' => $connection->resourceId,
                'session_id' => $connection->WAMP->sessionId,
                'storage_id' => $connection->WAMP->clientStorageId,
            ));
        }
    }

    /**
     * Called whenever a client errors.
     *
     * @param ClientErrorEvent $event
     */
    public function onClientError(ClientErrorEvent $event)
    {
        $connection = $event->getConnection();
        $e = $event->getException();

        if (null !== $this->logger) {
            $loggerContext = array(
                'connection_id' => $connection->resourceId,
                'session_id' => $connection->WAMP->sessionId,
            );

            $sid = $this->clientStorage->getStorageId($connection);

            $loggerContext['client'] = $this->clientStorage->getClient($sid, $connection);

            $this->logger->error(sprintf(
                'Connection error occurred %s in %s line %s',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ), $loggerContext);
        }
    }

    /**
     * @param ClientRejectedEvent $event
     */
    public function onClientRejected(ClientRejectedEvent $event)
    {
        if (null !== $this->logger) {
            $this->logger->warning('Client rejected, bad origin', [
                'origin' => $event->getOrigin(),
            ]);
        }
    }
}
