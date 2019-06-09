<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ClientManipulator implements ClientManipulatorInterface
{
    /**
     * @var ClientStorageInterface
     */
    protected $clientStorage;

    /**
     * @var WebsocketAuthenticationProviderInterface
     */
    protected $authenticationProvider;

    public function __construct(
        ClientStorageInterface $clientStorage,
        WebsocketAuthenticationProviderInterface $authenticationProvider
    ) {
        $this->clientStorage = $clientStorage;
        $this->authenticationProvider = $authenticationProvider;
    }

    public function getClient(ConnectionInterface $connection): TokenInterface
    {
        $storageId = $this->clientStorage->getStorageId($connection);

        try {
            return $this->clientStorage->getClient($storageId);
        } catch (ClientNotFoundException $e) {
            // User is gone due to ttl
            $this->authenticationProvider->authenticate($connection);

            return $this->getClient($connection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUser(ConnectionInterface $connection)
    {
        return $this->getClient($connection)->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function findByUsername(Topic $topic, string $username)
    {
        /** @var ConnectionInterface $connection */
        foreach ($topic as $connection) {
            $client = $this->getClient($connection);

            if ($client instanceof AnonymousToken) {
                continue;
            }

            if ($client->getUsername() === $username) {
                return ['client' => $client, 'connection' => $connection];
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(Topic $topic, bool $anonymous = false): array
    {
        $results = [];

        /** @var ConnectionInterface $connection */
        foreach ($topic as $connection) {
            $client = $this->getClient($connection);

            if (true !== $anonymous && $client instanceof AnonymousToken) {
                continue;
            }

            $results[] = [
                'client' => $client,
                'connection' => $connection,
            ];
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function findByRoles(Topic $topic, array $roles): array
    {
        $results = [];

        /** @var ConnectionInterface $connection */
        foreach ($topic as $connection) {
            $client = $this->getClient($connection);

            if ($client instanceof AnonymousToken) {
                continue;
            }

            foreach ($client->getRoles() as $role) {
                if (in_array($role->getRole(), $roles)) {
                    $results[] = [
                        'client' => $client,
                        'connection' => $connection,
                    ];

                    continue 1;
                }
            }
        }

        return $results;
    }
}
