<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Authentication;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface ConnectionRepositoryInterface
{
    /**
     * @return TokenConnection[]
     */
    public function findAll(Topic $topic, bool $anonymous = false): array;

    /**
     * @return TokenConnection[]
     */
    public function findAllByUsername(Topic $topic, string $username): array;

    /**
     * @return TokenConnection[]
     */
    public function findAllWithRoles(Topic $topic, array $roles): array;

    public function findTokenForConnection(ConnectionInterface $connection): TokenInterface;

    /**
     * @return string|\Stringable|UserInterface|null
     *
     * @note As of 4.0, the return type will change to `UserInterface|null`.
     */
    public function getUser(ConnectionInterface $connection);
}
