<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\StorageException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Log\NullLogger;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
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
     * @var string[]
     */
    protected $firewalls;

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $originChecker;

    /**
     * @param ClientStorageInterface   $clientStorage
     * @param SecurityContextInterface $securityContext
     * @param LoggerInterface          $logger
     * @param array                    $firewalls
     * @param bool                     $originChecker
     */
    public function __construct(
        ClientStorageInterface $clientStorage,
        SecurityContextInterface $securityContext,
        LoggerInterface $logger = null,
        $firewalls = array(),
        $originChecker
    ) {
        $this->clientStorage = $clientStorage;
        $this->firewalls = $firewalls;
        $this->securityContext = $securityContext;
        $this->logger = null === $logger ? new NullLogger() : $logger;
        $this->originChecker = $originChecker;
    }

    /**
     * @param ClientEvent $event
     *
     * @throws StorageException
     * @throws \Exception
     */
    public function onClientConnect(ClientEvent $event)
    {
        $conn = $event->getConnection();

        if (true === $this->originChecker && 1 === count($this->firewalls) && 'ws_firewall' === $this->firewalls[0]) {
            $this->logger->warning(sprintf('User firewall is not configured, we have set %s by default', $this->firewalls[0]));
        }

        $loggerContext = array(
            'connection_id' => $conn->resourceId,
            'session_id' => $conn->WAMP->sessionId,
        );

        $token = null;

        if (isset($conn->Session) && $conn->Session) {
            foreach ($this->firewalls as $firewall) {
                if (false !== $serializedToken = $conn->Session->get('_security_' . $firewall, false)) {
                    /** @var TokenInterface $token */
                    $token = unserialize($serializedToken);
                    break;
                }
            }
        }

        if (null === $token) {
            $token = new AnonymousToken($this->firewalls[0], 'anon-' . $conn->WAMP->sessionId);
        }

        $this->securityContext->setToken($token);

        $user = $token->getUser();

        $username = $user instanceof UserInterface
            ? $user->getUsername()
            : $user;

        try {
            $identifier = $this->clientStorage->getStorageId($conn, $username);
            $loggerContext['storage_id'] = $identifier;

            $this->clientStorage->addClient($identifier, $user);
            $conn->WAMP->clientStorageId = $identifier;

            $this->logger->info(sprintf(
                '%s connected [%]',
                $username,
                $user instanceof UserInterface ? implode(', ', $user->getRoles()) : array()
            ), $loggerContext);
        } catch (StorageException $e) {
            $this->logger->error(
                $e->getMessage(),
                $loggerContext
            );

            throw $e;
        }
    }

    /**
     * Called whenever a client disconnects.
     *
     * @param ClientEvent $event
     */
    public function onClientDisconnect(ClientEvent $event)
    {
        $conn = $event->getConnection();

        try {
            $user = $this->clientStorage->getClient($conn->WAMP->clientStorageId);

            $username = $user instanceof UserInterface
                ? $user->getUsername()
                : $user;


            $this->logger->info(sprintf(
                '%s disconnected [%]',
                $username,
                $user instanceof UserInterface ? implode(', ', $user->getRoles()) : array()
            ), array(
                'connection_id' => $conn->resourceId,
                'session_id' => $conn->WAMP->sessionId,
                'storage_id' => $conn->WAMP->clientStorageId,
            ));

        } catch (StorageException $e) {
            $this->logger->info(sprintf(
                '%s disconnected [%s]',
                'Expired user',
                ''
            ), array(
                'connection_id' => $conn->resourceId,
                'session_id' => $conn->WAMP->sessionId,
                'storage_id' => $conn->WAMP->clientStorageId,
            ));
        }

        $this->clientStorage->removeClient($conn->resourceId);
    }

    /**
     * Called whenever a client errors.
     *
     * @param ClientErrorEvent $event
     */
    public function onClientError(ClientErrorEvent $event)
    {
        $conn = $event->getConnection();
        $e = $event->getException();


        $loggerContext = array(
            'connection_id' => $conn->resourceId,
            'session_id' => $conn->WAMP->sessionId,
        );

        if ($this->clientStorage->hasClient($conn->resourceId)) {
            $loggerContext['client'] = $this->clientStorage->getClient($conn->WAMP->clientStorageId);
        }

        $this->logger->error(sprintf(
            'Connection error occurred %s in %s line %s',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ), $loggerContext);

    }

    /**
     * @param ClientRejectedEvent $event
     */
    public function onClientRejected(ClientRejectedEvent $event)
    {
        $this->logger->warning('Client rejected, bad origin', [
            'origin' => $event->getOrigin(),
        ]);
    }
}
