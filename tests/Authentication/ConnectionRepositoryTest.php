<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Authentication;

use Gos\Bundle\WebSocketBundle\Authentication\AuthenticatorInterface;
use Gos\Bundle\WebSocketBundle\Authentication\ConnectionRepository;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\TokenNotFoundException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface;
use Gos\Bundle\WebSocketBundle\Authentication\TokenConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;

final class ConnectionRepositoryTest extends TestCase
{
    /**
     * @var MockObject&TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var MockObject&AuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var ConnectionRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->authenticator = $this->createMock(AuthenticatorInterface::class);

        $this->repository = new ConnectionRepository($this->tokenStorage, $this->authenticator);
    }

    public function testFindTokenForConnection(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        $storageId = 42;

        /** @var MockObject&AbstractToken $token */
        $token = $this->createMock(AbstractToken::class);

        $this->tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn((string) $storageId);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->with($storageId)
            ->willReturn($token);

        self::assertSame($token, $this->repository->findTokenForConnection($connection));
    }

    public function testFindTokenForConnectionAfterReauthenticating(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        $storageId = 42;

        /** @var MockObject&AbstractToken $token */
        $token = $this->createMock(AbstractToken::class);

        $this->tokenStorage->expects(self::exactly(2))
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn((string) $storageId);

        $this->tokenStorage->expects(self::exactly(2))
            ->method('getToken')
            ->with($storageId)
            ->willReturnOnConsecutiveCalls(
                self::throwException(new TokenNotFoundException()),
                $token
            );

        $this->authenticator->expects(self::once())
            ->method('authenticate')
            ->with($connection);

        self::assertSame($token, $this->repository->findTokenForConnection($connection));
    }

    public function testAllConnectionsForAUserCanBeFoundByUsername(): void
    {
        $usernameMethod = method_exists(AbstractToken::class, 'getUserIdentifier') ? 'getUserIdentifier' : 'getUsername';

        /** @var MockObject&ConnectionInterface $connection1 */
        $connection1 = $this->createMock(ConnectionInterface::class);

        /** @var MockObject&ConnectionInterface $connection2 */
        $connection2 = $this->createMock(ConnectionInterface::class);

        /** @var MockObject&ConnectionInterface $connection3 */
        $connection3 = $this->createMock(ConnectionInterface::class);

        $storageId1 = 42;
        $storageId2 = 43;
        $storageId3 = 44;

        $username1 = 'user';
        $username2 = 'guest';

        /** @var MockObject&AbstractToken $token1 */
        $token1 = $this->createMock(AbstractToken::class);
        $token1->expects(self::once())
            ->method($usernameMethod)
            ->willReturn($username1);

        /** @var MockObject&AbstractToken $token2 */
        $token2 = $this->createMock(AbstractToken::class);
        $token2->expects(self::once())
            ->method($usernameMethod)
            ->willReturn($username1);

        /** @var MockObject&AbstractToken $token3 */
        $token3 = $this->createMock(AbstractToken::class);
        $token3->expects(self::once())
            ->method($usernameMethod)
            ->willReturn($username2);

        $this->tokenStorage->expects(self::exactly(3))
            ->method('generateStorageId')
            ->withConsecutive(
                [$connection1],
                [$connection2],
                [$connection3]
            )
            ->willReturnOnConsecutiveCalls(
                (string) $storageId1,
                (string) $storageId2,
                (string) $storageId3
            );

        $this->tokenStorage->expects(self::exactly(3))
            ->method('getToken')
            ->withConsecutive(
                [$storageId1],
                [$storageId2],
                [$storageId3]
            )
            ->willReturnOnConsecutiveCalls(
                $token1,
                $token2,
                $token3
            );

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2, $connection3]));

        self::assertEquals(
            [
                new TokenConnection($token1, $connection1),
                new TokenConnection($token2, $connection2),
            ],
            $this->repository->findAllByUsername($topic, $username1)
        );
    }

    public function testFetchingAllConnectionsByDefaultOnlyReturnsAuthenticatedUsers(): void
    {
        /** @var MockObject&ConnectionInterface $connection1 */
        $connection1 = $this->createMock(ConnectionInterface::class);

        /** @var MockObject&ConnectionInterface $connection2 */
        $connection2 = $this->createMock(ConnectionInterface::class);

        $storageId1 = 42;
        $storageId2 = 84;

        /** @var MockObject&TokenInterface $authenticatedToken */
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $authenticatedToken->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class));

        /** @var MockObject&TokenInterface $guestToken */
        $guestToken = $this->createMock(TokenInterface::class);
        $guestToken->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $this->tokenStorage->expects(self::exactly(2))
            ->method('generateStorageId')
            ->withConsecutive(
                [$connection1],
                [$connection2]
            )
            ->willReturnOnConsecutiveCalls(
                (string) $storageId1,
                (string) $storageId2
            );

        $this->tokenStorage->expects(self::exactly(2))
            ->method('getToken')
            ->withConsecutive(
                [$storageId1],
                [$storageId2]
            )
            ->willReturnOnConsecutiveCalls(
                $authenticatedToken,
                $guestToken
            );

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2]));

        self::assertEquals(
            [
                new TokenConnection($authenticatedToken, $connection1),
            ],
            $this->repository->findAll($topic)
        );
    }

    public function testFetchingAllConnectionsWithAnonymousFlagReturnsAllConnectedUsers(): void
    {
        /** @var MockObject&ConnectionInterface $connection1 */
        $connection1 = $this->createMock(ConnectionInterface::class);

        /** @var MockObject&ConnectionInterface $connection2 */
        $connection2 = $this->createMock(ConnectionInterface::class);

        $storageId1 = 42;
        $storageId2 = 84;

        /** @var MockObject&AbstractToken $authenticatedToken */
        $authenticatedToken = $this->createMock(AbstractToken::class);

        /** @var MockObject&TokenInterface $guestToken */
        $guestToken = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::exactly(2))
            ->method('generateStorageId')
            ->withConsecutive(
                [$connection1],
                [$connection2]
            )
            ->willReturnOnConsecutiveCalls(
                (string) $storageId1,
                (string) $storageId2
            );

        $this->tokenStorage->expects(self::exactly(2))
            ->method('getToken')
            ->withConsecutive(
                [$storageId1],
                [$storageId2]
            )
            ->willReturnOnConsecutiveCalls(
                $authenticatedToken,
                $guestToken
            );

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2]));

        self::assertEquals(
            [
                new TokenConnection($authenticatedToken, $connection1),
                new TokenConnection($guestToken, $connection2),
            ],
            $this->repository->findAll($topic, true)
        );
    }

    public function testFetchingAllUsersWithDefinedRolesOnlyReturnsMatchingUsers(): void
    {
        /** @var MockObject&ConnectionInterface $connection1 */
        $connection1 = $this->createMock(ConnectionInterface::class);

        /** @var MockObject&ConnectionInterface $connection2 */
        $connection2 = $this->createMock(ConnectionInterface::class);

        /** @var MockObject&ConnectionInterface $connection3 */
        $connection3 = $this->createMock(ConnectionInterface::class);

        $storageId1 = 42;
        $storageId2 = 84;
        $storageId3 = 126;

        /** @var MockObject&TokenInterface $authenticatedToken1 */
        $authenticatedToken1 = $this->createMock(TokenInterface::class);

        /** @var MockObject&TokenInterface $authenticatedToken2 */
        $authenticatedToken2 = $this->createMock(TokenInterface::class);

        /** @var MockObject&TokenInterface $guestToken */
        $guestToken = $this->createMock(TokenInterface::class);

        if (method_exists(TokenInterface::class, 'getRoleNames')) {
            $authenticatedToken1->expects(self::once())
                ->method('getRoleNames')
                ->willReturn(['ROLE_USER', 'ROLE_STAFF']);

            $authenticatedToken2->expects(self::once())
                ->method('getRoleNames')
                ->willReturn(['ROLE_USER']);

            $guestToken->expects(self::once())
                ->method('getRoleNames')
                ->willReturn([]);
        } else {
            $authenticatedToken1->expects(self::once())
                ->method('getRoles')
                ->willReturn([new Role('ROLE_USER'), new Role('ROLE_STAFF')]);

            $authenticatedToken2->expects(self::once())
                ->method('getRoles')
                ->willReturn([new Role('ROLE_USER')]);

            $guestToken->expects(self::once())
                ->method('getRoles')
                ->willReturn([]);
        }

        $this->tokenStorage->expects(self::exactly(3))
            ->method('generateStorageId')
            ->withConsecutive(
                [$connection1],
                [$connection2],
                [$connection3]
            )
            ->willReturnOnConsecutiveCalls(
                (string) $storageId1,
                (string) $storageId2,
                (string) $storageId3
            );

        $this->tokenStorage->expects(self::exactly(3))
            ->method('getToken')
            ->withConsecutive(
                [$storageId1],
                [$storageId2],
                [$storageId3]
            )
            ->willReturnOnConsecutiveCalls(
                $authenticatedToken1,
                $authenticatedToken2,
                $guestToken
            );

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2, $connection3]));

        self::assertEquals(
            [
                new TokenConnection($authenticatedToken1, $connection1),
            ],
            $this->repository->findAllWithRoles($topic, ['ROLE_STAFF'])
        );
    }
}
