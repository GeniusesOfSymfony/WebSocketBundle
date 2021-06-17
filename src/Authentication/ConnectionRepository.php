<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ConnectionRepository implements ConnectionRepositoryInterface
{
    private TokenStorageInterface $tokenStorage;
    private AuthenticatorInterface $authenticator;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticatorInterface $authenticator
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticator = $authenticator;
    }

    /**
     * @return TokenConnection[]
     */
    public function findAll(Topic $topic, bool $anonymous = false): array
    {
        $result = [];

        /** @var ConnectionInterface $connection */
        foreach ($topic as $connection) {
            $client = $this->findTokenForConnection($connection);

            if (!$anonymous && !($client->getUser() instanceof UserInterface)) {
                continue;
            }

            $result[] = new TokenConnection($client, $connection);
        }

        return $result;
    }

    /**
     * @return TokenConnection[]
     */
    public function findAllByUsername(Topic $topic, string $username): array
    {
        $result = [];

        /** @var ConnectionInterface $connection */
        foreach ($topic as $connection) {
            $client = $this->findTokenForConnection($connection);

            $clientUsername = method_exists($client, 'getUserIdentifier') ? $client->getUserIdentifier() : $client->getUsername();

            if ($clientUsername === $username) {
                $result[] = new TokenConnection($client, $connection);
            }
        }

        return $result;
    }

    /**
     * @return TokenConnection[]
     */
    public function findAllWithRoles(Topic $topic, array $roles): array
    {
        $result = [];

        /** @var ConnectionInterface $connection */
        foreach ($topic as $connection) {
            $client = $this->findTokenForConnection($connection);

            if (method_exists($client, 'getRoleNames')) {
                foreach ($client->getRoleNames() as $role) {
                    if (\in_array($role, $roles)) {
                        $result[] = new TokenConnection($client, $connection);

                        continue 2;
                    }
                }
            } else {
                foreach ($client->getRoles() as $role) {
                    if (\in_array($role->getRole(), $roles)) {
                        $result[] = new TokenConnection($client, $connection);

                        continue 2;
                    }
                }
            }
        }

        return $result;
    }

    public function findTokenForConnection(ConnectionInterface $connection): TokenInterface
    {
        $storageId = $this->tokenStorage->generateStorageId($connection);

        try {
            return $this->tokenStorage->getToken($storageId);
        } catch (TokenNotFoundException $e) {
            // Generally this would mean the token expired from storage, attempt to re-authenticate the connection
            $this->authenticator->authenticate($connection);

            return $this->findTokenForConnection($connection);
        }
    }

    /**
     * @return string|\Stringable|UserInterface|null
     *
     * @note As of 4.0, the return type will change to `UserInterface|null`.
     */
    public function getUser(ConnectionInterface $connection)
    {
        $user = $this->findTokenForConnection($connection)->getUser();

        if (null !== $user && !($user instanceof UserInterface)) {
            trigger_deprecation('gos/web-socket-bundle', '3.14', 'Retrieving a user that is not an instance of %s is deprecated in %s().', UserInterface::class, __METHOD__);
        }

        return $user;
    }
}
