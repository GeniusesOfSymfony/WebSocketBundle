<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication;

use Gos\Bundle\WebSocketBundle\Authentication\Provider\AuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;

final class Authenticator implements AuthenticatorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var AuthenticationProviderInterface[]
     */
    private iterable $providers;

    private TokenStorageInterface $tokenStorage;

    /**
     * @param AuthenticationProviderInterface[] $providers
     */
    public function __construct(iterable $providers, TokenStorageInterface $tokenStorage)
    {
        $this->providers = $providers;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Attempts to authenticate the current connection.
     */
    public function authenticate(ConnectionInterface $connection): void
    {
        foreach ($this->providers as $provider) {
            if (!$provider->supports($connection)) {
                if (null !== $this->logger) {
                    $this->logger->debug(sprintf('Skipping the "%s" authentication provider as it did not support the connection.', \get_class($provider)));
                }

                continue;
            }

            $token = $provider->authenticate($connection);

            $id = $this->tokenStorage->generateStorageId($connection);

            $this->tokenStorage->addToken($id, $token);

            if (null !== $this->logger) {
                $this->logger->info(
                    sprintf(
                        'User "%s" authenticated to websocket server',
                        method_exists($token, 'getUserIdentifier') ? $token->getUserIdentifier() : $token->getUsername()
                    ),
                    [
                        'connection_id' => $connection->resourceId,
                        'session_id' => $connection->WAMP->sessionId,
                        'storage_id' => $id,
                    ]
                );
            }

            break;
        }
    }
}
