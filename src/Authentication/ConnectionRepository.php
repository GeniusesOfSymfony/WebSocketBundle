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
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private AuthenticatorInterface $authenticator,
    ) {
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

            foreach ($client->getRoleNames() as $role) {
                if (\in_array($role, $roles, true)) {
                    $result[] = new TokenConnection($client, $connection);

                    continue 2;
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
     * @return string|\Stringable|UserInterface
     */
    public function getUser(ConnectionInterface $connection)
    {
        return $this->findTokenForConnection($connection)->getUser();
    }
}
