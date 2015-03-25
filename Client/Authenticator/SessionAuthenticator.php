<?php

namespace Gos\Bundle\WebSocketBundle\Client\Authenticator;

use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SessionAuthenticator implements AuthenticatorInterface
{
    /** @var  string[] */
    protected $firewalls;

    /** @var  LoggerInterface */
    protected $logger;

    /** @var  SecurityContextInterface */
    protected $securityContext;

    /**
     * @param string[]                 $firewalls
     * @param SecurityContextInterface $securityContext
     * @param LoggerInterface          $logger
     */
    public function __construct(
        Array $firewalls,
        SecurityContextInterface $securityContext,
        LoggerInterface $logger = null
    ) {
        $this->firewalls = $firewalls;
        $this->logger = $logger;
        $this->securityContext = $securityContext;
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return string|UserInterface
     */
    public function authenticate(ConnectionInterface $connection)
    {
        if (null !== $this->securityContext->getToken()) { //Simply reload the user against
            $token = $this->securityContext->getToken();

            return $token->getUser();
        }

        if (1 === count($this->firewalls) && 'ws_firewall' === $this->firewalls[0]) {
            $this->logger->warning(sprintf('User firewall is not configured, we have set %s by default', $this->firewalls[0]));
        }

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

        $this->securityContext->setToken($token);

        return $token->getUser();
    }
}
