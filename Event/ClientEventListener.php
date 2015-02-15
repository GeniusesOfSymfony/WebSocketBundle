<?php
namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Gos\Bundle\WebSocketBundle\Client\StorageException;
use Psr\Log\LoggerInterface;
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
     * @param ClientStorage $clientStorage
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
     * @param ClientStorage            $clientStorage
     * @param SecurityContextInterface $securityContext
     * @param LoggerInterface          $logger
     * @param array                    $firewalls
     */
    public function __construct(
        ClientStorage $clientStorage,
        SecurityContextInterface $securityContext,
        LoggerInterface $logger = null,
        $firewalls = array()
    ) {
        $this->clientStorage = $clientStorage;
        $this->firewalls = $firewalls;
        $this->securityContext = $securityContext;
        $this->logger = $logger;
    }

    /**
     * Called whenever a client connects
     *
     * @param ClientEvent $event
     */
    public function onClientConnect(ClientEvent $event)
    {
        $conn = $event->getConnection();

        if (null !== $this->logger) {
            $loggerContext = array(
                'connection_id' => $conn->resourceId,
                'session_id' => $conn->WAMP->sessionId,
            );
        }

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

        $username = $this->securityContext->isGranted('IS_AUTHENTICATED_FULLY')
            ? $user->getUsername()
            : $user;

        try {
            $this->clientStorage->addClient($conn->resourceId, $user);

            if (null !== $this->logger) {
                $this->logger->info(sprintf(
                    '%s connected [%]',
                    $username,
                    $user instanceof UserInterface ? implode(', ', $user->getRoles()) : array()
                ), $loggerContext);
            }
        } catch (StorageException $e) {
            if (null !== $this->logger) {
                $this->logger->error(
                    $e->getMessage(),
                    $loggerContext
                );
            }

            throw $e;
        }
    }

    /**
     * Called whenever a client disconnects
     *
     * @param ClientEvent $event
     */
    public function onClientDisconnect(ClientEvent $event)
    {
        $conn = $event->getConnection();

        $user = $this->clientStorage->getClient($conn->resourceId);

        $username = $this->securityContext->isGranted('IS_AUTHENTICATED_FULLY')
            ? $user->getUsername()
            : $user;

        if (null !== $this->logger) {
            $this->logger->info(sprintf(
                '%s disconnected [%]',
                $username,
                $user instanceof UserInterface ? implode(', ', $user->getRoles()) : array()
            ), array(
                'connection_id' => $conn->resourceId,
                'session_id' => $conn->WAMP->sessionId,
            ));
        }

        $this->clientStorage->removeClient($conn->resourceId);
    }

    /**
     * Called whenever a client errors
     *
     * @param ClientErrorEvent $event
     */
    public function onClientError(ClientErrorEvent $event)
    {
        $conn = $event->getConnection();
        $e = $event->getException();

        if (null !== $this->logger) {
            $loggerContext = array(
                'connection_id' => $conn->resourceId,
                'session_id' => $conn->WAMP->sessionId,
            );

            if ($this->clientStorage->hasClient($conn->resourceId)) {
                $loggerContext['client'] = $this->clientStorage->getClient($conn->resourceId);
            }

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
                'origin' => $event->getOrigin()
            ]);
        }
    }
}
