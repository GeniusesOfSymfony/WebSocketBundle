<?php

namespace Gos\Bundle\WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\User\UserInterface;

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

    /**
     * @param ClientStorageInterface                   $clientStorage
     * @param WebsocketAuthenticationProviderInterface $authenticationProvider
     */
    public function __construct(ClientStorageInterface $clientStorage, WebsocketAuthenticationProviderInterface $authenticationProvider)
    {
        $this->clientStorage = $clientStorage;
        $this->authenticationProvider = $authenticationProvider;
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return false|string|\Symfony\Component\Security\Core\User\UserInterface
     */
    public function getClient(ConnectionInterface $connection)
    {
        $storageId = $this->clientStorage->getStorageId($connection);

        try {
            return $this->clientStorage->getClient($storageId);
        } catch (ClientNotFoundException $e) { //User is gone due to ttl
            $this->authenticationProvider->authenticate($connection);

            return $this->getClient($connection);
        }
    }

    /**
     * @param Topic  $topic
     * @param string $username
     *
     * @return false|string|\Symfony\Component\Security\Core\User\UserInterface
     */
    public function findByUsername(Topic $topic, $username)
    {
        foreach ($topic as $connection) {
            $client = $this->getClient($connection);

            if ($client instanceof AnonymousToken || false === $client) {
                continue;
            }

            if ($client->getUsername() === $username) {
                return ['client' => $client, 'connection' => $connection];
            }
        }

        return false;
    }

    /**
     * @param Topic $topic
     * @param bool  $anonymous
     *
     * @return false|string|UserInterface
     */
    public function getAll(Topic $topic, $anonymous = false)
    {
        $results = [];

        foreach ($topic as $connection) {
            $client = $this->getClient($connection);

            if (true !== $anonymous && ($client instanceof AnonymousToken || false === $client)) {
                continue;
            }

            $results[] = [
                'client' => $client,
                'connection' => $connection,
            ];
        }

        return empty($results) ? false : $results;
    }

    /**
     * @param Topic $topic
     * @param array $roles
     *
     * @return UserInterface[]
     */
    public function findByRoles(Topic $topic, array $roles)
    {
        $results = [];

        foreach ($topic as $connection) {
            $client = $this->getClient($connection);

            if ($client instanceof AnonymousToken || false === $client) {
                continue;
            }

            foreach ($client->getRoles() as $role) {
                if (in_array($role, $roles)) {
                    $results[] = [
                        'client' => $client,
                        'connection' => $connection,
                    ];

                    continue 1;
                }
            }
        }

        return empty($results) ? false : $results;
    }
}
