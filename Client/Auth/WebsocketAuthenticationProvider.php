<?php

namespace Gos\Bundle\WebSocketBundle\Client\Auth;

use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\HttpKernel\Log\NullLogger;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class WebsocketAuthenticationProvider implements WebsocketAuthenticationProviderInterface
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var array
     */
    protected $firewalls;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ClientStorageInterface
     */
    protected $clientStorage;

    /**
     * @param SecurityContextInterface $securityContext
     * @param array                    $firewalls
     * @param ClientStorageInterface   $clientStorage
     * @param LoggerInterface          $logger
     */
    public function __construct(
        SecurityContextInterface $securityContext,
        $firewalls = array(),
        ClientStorageInterface $clientStorage,
        LoggerInterface $logger = null
    ) {
        $this->securityContext = $securityContext;
        $this->firewalls = $firewalls;
        $this->clientStorage = $clientStorage;
        $this->logger = null === $logger ? new NullLogger() : $logger;
    }

    protected function getToken(ConnectionInterface $connection)
    {
        $token = null;

        if (isset($connection->Session) && $connection->Session) {
            foreach ($this->firewalls as $firewall) {
                if (false !== $serializedToken = $connection->Session->get('_security_' . $firewall, false)) {
                    /** @var TokenInterface $token */
                    $token = unserialize($serializedToken);
                    break;
                }
            }
        }

        if (null === $token) {
            $token = new AnonymousToken($this->firewalls[0], 'anon-' . $connection->WAMP->sessionId);
        }

        if ($this->securityContext->getToken() !== $token) {
            $this->securityContext->setToken($token);
        }

        return $token;
    }

    /**
     * @param TokenInterface $token
     */
    public function authenticate(ConnectionInterface $conn)
    {
        if (1 === count($this->firewalls) && 'ws_firewall' === $this->firewalls[0]) {
            $this->logger->warning(sprintf(
                'User firewall is not configured, we have set %s by default',
                $this->firewalls[0])
            );
        }

        $loggerContext = array(
            'connection_id' => $conn->resourceId,
            'session_id' => $conn->WAMP->sessionId,
        );

        $token = $this->getToken($conn);
        $user = $token->getUser();
        $username = $user instanceof UserInterface ? $user->getUsername() : $user;

        try {
            $identifier = $this->clientStorage->getStorageId($conn, $username);
        } catch (StorageException $e) {
            $this->logger->error(
                $e->getMessage(),
                $loggerContext
            );

            throw $e;
        }

        $loggerContext['storage_id'] = $identifier;
        $this->clientStorage->addClient($identifier, $token->getUser());
        $conn->WAMP->clientStorageId = $identifier;

        $this->logger->info(sprintf(
            '%s connected [%]',
            $username,
            $user instanceof UserInterface ? implode(', ', $user->getRoles()) : array()
        ), $loggerContext);

        return $token;
    }

    /**
     * @param TokenInterface $token
     *
     * @return bool
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof WebsocketToken;
    }
}
