<?php

namespace Gos\Bundle\WebSocketBundle\Client\Auth;

use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class WebsocketAuthenticationProvider implements WebsocketAuthenticationProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var array
     */
    protected $firewalls;

    /**
     * @var ClientStorageInterface
     */
    protected $clientStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param array $firewalls
     * @param ClientStorageInterface $clientStorage
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        $firewalls = [],
        ClientStorageInterface $clientStorage
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->firewalls = $firewalls;
        $this->clientStorage = $clientStorage;
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return TokenInterface
     */
    protected function getToken(ConnectionInterface $connection)
    {
        $token = null;

        if (isset($connection->Session) && $connection->Session) {
            foreach ($this->firewalls as $firewall) {
                if (false !== $serializedToken = $connection->Session->get('_security_'.$firewall, false)) {
                    /** @var TokenInterface $token */
                    $token = unserialize($serializedToken);

                    break;
                }
            }
        }

        if (null === $token) {
            $token = new AnonymousToken($this->firewalls[0], 'anon-'.$connection->WAMP->sessionId);
        }

        if ($this->tokenStorage->getToken() !== $token) {
            $this->tokenStorage->setToken($token);
        }

        return $token;
    }

    /**
     * @param ConnectionInterface $conn
     *
     * @return TokenInterface
     *
     * @throws StorageException
     * @throws \Exception
     */
    public function authenticate(ConnectionInterface $conn)
    {
        if (1 === count($this->firewalls) && 'ws_firewall' === $this->firewalls[0]) {
            if ($this->logger) {
                $this->logger->warning(
                    sprintf(
                        'User firewall is not configured, we have set %s by default',
                        $this->firewalls[0]
                    )
                );
            }
        }

        $loggerContext = [
            'connection_id' => $conn->resourceId,
            'session_id' => $conn->WAMP->sessionId,
        ];

        $token = $this->getToken($conn);
        $user = $token->getUser();
        $username = $user instanceof UserInterface ? $user->getUsername() : $user;

        $identifier = $this->clientStorage->getStorageId($conn);

        $loggerContext['storage_id'] = $identifier;
        $this->clientStorage->addClient($identifier, $token->getUser());
        $conn->WAMP->clientStorageId = $identifier;

        if ($this->logger) {
            $this->logger->info(
                sprintf(
                    '%s connected',
                    $username
                ),
                $loggerContext
            );
        }

        return $token;
    }
}
