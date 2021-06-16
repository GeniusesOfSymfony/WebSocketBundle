<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientConnection;
use Gos\Bundle\WebSocketBundle\Client\ClientManipulator;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ClientManipulatorTest extends TestCase
{
    /**
     * @var MockObject&ClientStorageInterface
     */
    private $clientStorage;

    /**
     * @var MockObject&WebsocketAuthenticationProviderInterface
     */
    private $authenticationProvider;

    /**
     * @var ClientManipulator
     */
    private $manipulator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientStorage = $this->createMock(ClientStorageInterface::class);
        $this->authenticationProvider = $this->createMock(WebsocketAuthenticationProviderInterface::class);

        $this->manipulator = new ClientManipulator($this->clientStorage, $this->authenticationProvider);
    }

    public function testGetClientForConnection(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $storageId = 42;
        $client = $this->createMock(AbstractToken::class);

        $this->clientStorage->expects(self::once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn((string) $storageId);

        $this->clientStorage->expects(self::once())
            ->method('getClient')
            ->with($storageId)
            ->willReturn($client);

        self::assertSame($client, $this->manipulator->getClient($connection));
    }

    public function testGetClientForConnectionAfterReauthenticating(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $storageId = 42;
        $client = $this->createMock(AbstractToken::class);

        $this->clientStorage->expects(self::exactly(2))
            ->method('getStorageId')
            ->with($connection)
            ->willReturn((string) $storageId);

        $this->clientStorage->expects(self::exactly(2))
            ->method('getClient')
            ->with($storageId)
            ->willReturnOnConsecutiveCalls(
                self::throwException(new ClientNotFoundException()),
                $client
            );

        $this->authenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($connection);

        self::assertSame($client, $this->manipulator->getClient($connection));
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

        /** @var MockObject&AbstractToken $client1 */
        $client1 = $this->createMock(AbstractToken::class);
        $client1->expects(self::once())
            ->method($usernameMethod)
            ->willReturn($username1);

        /** @var MockObject&AbstractToken $client2 */
        $client2 = $this->createMock(AbstractToken::class);
        $client2->expects(self::once())
            ->method($usernameMethod)
            ->willReturn($username1);

        /** @var MockObject&AbstractToken $client3 */
        $client3 = $this->createMock(AbstractToken::class);
        $client3->expects(self::once())
            ->method($usernameMethod)
            ->willReturn($username2);

        $this->clientStorage->expects(self::exactly(3))
            ->method('getStorageId')
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

        $this->clientStorage->expects(self::exactly(3))
            ->method('getClient')
            ->withConsecutive(
                [$storageId1],
                [$storageId2],
                [$storageId3]
            )
            ->willReturnOnConsecutiveCalls(
                $client1,
                $client2,
                $client3
            );

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2, $connection3]));

        self::assertEquals(
            [
                new ClientConnection($client1, $connection1),
                new ClientConnection($client2, $connection2),
            ],
            $this->manipulator->findAllByUsername($topic, $username1)
        );
    }

    public function testFetchingAllConnectionsByDefaultOnlyReturnsAuthenticatedUsers(): void
    {
        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection2 = $this->createMock(ConnectionInterface::class);

        $storageId1 = 42;
        $storageId2 = 84;

        $authenticatedClient = $this->createMock(AbstractToken::class);
        $guestClient = $this->createMock(AnonymousToken::class);

        $this->clientStorage->expects(self::exactly(2))
            ->method('getStorageId')
            ->withConsecutive(
                [$connection1],
                [$connection2]
            )
            ->willReturnOnConsecutiveCalls(
                (string) $storageId1,
                (string) $storageId2
            );

        $this->clientStorage->expects(self::exactly(2))
            ->method('getClient')
            ->withConsecutive(
                [$storageId1],
                [$storageId2]
            )
            ->willReturnOnConsecutiveCalls(
                $authenticatedClient,
                $guestClient
            );

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2]));

        self::assertEquals(
            [
                new ClientConnection($authenticatedClient, $connection1),
            ],
            $this->manipulator->getAll($topic)
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

        /** @var MockObject&AbstractToken $authenticatedClient */
        $authenticatedClient = $this->createMock(AbstractToken::class);

        /** @var MockObject&AnonymousToken $guestClient */
        $guestClient = $this->createMock(AnonymousToken::class);

        $this->clientStorage->expects(self::exactly(2))
            ->method('getStorageId')
            ->withConsecutive(
                [$connection1],
                [$connection2]
            )
            ->willReturnOnConsecutiveCalls(
                (string) $storageId1,
                (string) $storageId2
            );

        $this->clientStorage->expects(self::exactly(2))
            ->method('getClient')
            ->withConsecutive(
                [$storageId1],
                [$storageId2]
            )
            ->willReturnOnConsecutiveCalls(
                $authenticatedClient,
                $guestClient
            );

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2]));

        self::assertEquals(
            [
                new ClientConnection($authenticatedClient, $connection1),
                new ClientConnection($guestClient, $connection2),
            ],
            $this->manipulator->getAll($topic, true)
        );
    }

    public function testFetchingAllUsersWithDefinedRolesOnlyReturnsMatchingUsers(): void
    {
        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection2 = $this->createMock(ConnectionInterface::class);
        $connection3 = $this->createMock(ConnectionInterface::class);

        $storageId1 = 42;
        $storageId2 = 84;
        $storageId3 = 126;

        $authenticatedClient1 = $this->createMock(UsernamePasswordToken::class);
        $authenticatedClient1->expects(self::once())
            ->method('getRoleNames')
            ->willReturn(['ROLE_USER', 'ROLE_STAFF']);

        $authenticatedClient2 = $this->createMock(UsernamePasswordToken::class);
        $authenticatedClient2->expects(self::once())
            ->method('getRoleNames')
            ->willReturn(['ROLE_USER']);

        $guestClient = $this->createMock(AnonymousToken::class);

        $this->clientStorage->expects(self::exactly(3))
            ->method('getStorageId')
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

        $this->clientStorage->expects(self::exactly(3))
            ->method('getClient')
            ->withConsecutive(
                [$storageId1],
                [$storageId2],
                [$storageId3]
            )
            ->willReturnOnConsecutiveCalls(
                $authenticatedClient1,
                $authenticatedClient2,
                $guestClient
            );

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2, $connection3]));

        self::assertEquals(
            [
                new ClientConnection($authenticatedClient1, $connection1),
            ],
            $this->manipulator->findByRoles($topic, ['ROLE_STAFF'])
        );
    }
}
