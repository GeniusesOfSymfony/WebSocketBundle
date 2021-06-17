<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Client\Auth;

use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trigger_deprecation('gos/web-socket-bundle', '3.11', 'The "%s" class is deprecated and will be removed in 4.0, use the new websocket authentication API instead.', WebsocketAuthenticationProvider::class);

/**
 * @deprecated to be removed in 4.0, use the new websocket authentication API instead
 */
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

        $token = $this->getToken($conn);

        $identifier = $this->clientStorage->getStorageId($conn);

        $this->clientStorage->addClient($identifier, $token);

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf(
                    '%s connected',
                    method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername()
                ),
                [
                    'connection_id' => $conn->resourceId,
                    'session_id' => $conn->WAMP->sessionId,
                    'storage_id' => $identifier,
                ]
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
            if (class_exists(NullToken::class)) {
                $token = new NullToken();
            } else {
                $token = new AnonymousToken($this->firewalls[0], 'anon-'.$connection->WAMP->sessionId);
            }
        }

        return $token;
    }
}
