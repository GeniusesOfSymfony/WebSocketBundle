<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Auth;

use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class WebsocketAuthenticationProvider implements WebsocketAuthenticationProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ClientStorageInterface $clientStorage;

    /**
     * @var string[]
     */
    private array $firewalls = [];

    /**
     * @param string[] $firewalls
     */
    public function __construct(ClientStorageInterface $clientStorage, array $firewalls = [])
    {
        $this->clientStorage = $clientStorage;
        $this->firewalls = $firewalls;
    }

    public function authenticate(ConnectionInterface $conn): TokenInterface
    {
        if (1 === \count($this->firewalls) && 'ws_firewall' === $this->firewalls[0]) {
            if (null !== $this->logger) {
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

        $identifier = $this->clientStorage->getStorageId($conn);

        $loggerContext['storage_id'] = $identifier;
        $this->clientStorage->addClient($identifier, $token);

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf(
                    '%s connected',
                    $token->getUsername()
                ),
                $loggerContext
            );
        }

        return $token;
    }

    private function getToken(ConnectionInterface $connection): TokenInterface
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

        return $token;
    }
}
