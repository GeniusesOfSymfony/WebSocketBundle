<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication\Provider;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The session authentication provider uses the HTTP session for your website's frontend for authenticating to the websocket server.
 *
 * The provider will by default attempt to authenticate with any of your site's configured firewalls, using the token
 * from the first matched firewall in your configuration. You may optionally configure the provider to use only selected
 * firewalls for authenticated.
 */
final class SessionAuthenticationProvider implements AuthenticationProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private TokenStorageInterface $tokenStorage;

    /**
     * @var string[]
     */
    private array $firewalls;

    /**
     * @param string[] $firewalls
     */
    public function __construct(TokenStorageInterface $tokenStorage, array $firewalls)
    {
        $this->tokenStorage = $tokenStorage;
        $this->firewalls = $firewalls;
    }

    public function supports(ConnectionInterface $connection): bool
    {
        return isset($connection->Session) && $connection->Session instanceof SessionInterface;
    }

    public function authenticate(ConnectionInterface $connection): TokenInterface
    {
        $token = $this->getToken($connection);

        $storageId = $this->tokenStorage->generateStorageId($connection);

        $this->tokenStorage->addToken($storageId, $token);

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf(
                    '%s connected',
                    method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername()
                ),
                [
                    'connection_id' => $connection->resourceId,
                    'session_id' => $connection->WAMP->sessionId,
                    'storage_id' => $storageId,
                ]
            );
        }

        return $token;
    }

    private function getToken(ConnectionInterface $connection): TokenInterface
    {
        $token = null;

        foreach ($this->firewalls as $firewall) {
            if (false !== $serializedToken = $connection->Session->get('_security_'.$firewall, false)) {
                /** @var TokenInterface $token */
                $token = unserialize($serializedToken);

                break;
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
