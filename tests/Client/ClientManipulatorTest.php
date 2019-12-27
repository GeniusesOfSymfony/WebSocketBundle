<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientManipulator;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ClientManipulatorTest extends TestCase
{
    /**
     * @var MockObject|ClientStorageInterface
     */
    private $clientStorage;

    /**
     * @var MockObject|WebsocketAuthenticationProviderInterface
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
        $client = $this->createMock(TokenInterface::class);

        $this->clientStorage->expects($this->once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn((string) $storageId);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->with($storageId)
            ->willReturn($client);

        $this->assertSame($client, $this->manipulator->getClient($connection));
    }

    public function testGetClientForConnectionAfterReauthenticating(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $storageId = 42;
        $client = $this->createMock(TokenInterface::class);

        /*
         * $this->at() uses the index of all calls to the mocked object, the indexing is:
         *
         * 0: getStorageId
         * 1: getClient
         * 2: getStorageId
         * 3: getClient
         */

        $this->clientStorage->expects($this->exactly(2))
            ->method('getStorageId')
            ->with($connection)
            ->willReturn((string) $storageId);

        $this->clientStorage->expects($this->at(1))
            ->method('getClient')
            ->with($storageId)
            ->willThrowException(new ClientNotFoundException());

        $this->authenticationProvider->expects($this->once())
            ->method('authenticate')
            ->with($connection);

        $this->clientStorage->expects($this->at(3))
            ->method('getClient')
            ->with($storageId)
            ->willReturn($client);

        $this->assertSame($client, $this->manipulator->getClient($connection));
    }

    public function testAllConnectionsForAUserCanBeFoundByUsername(): void
    {
        /** @var MockObject|ConnectionInterface $connection1 */
        $connection1 = $this->createMock(ConnectionInterface::class);

        /** @var MockObject|ConnectionInterface $connection2 */
        $connection2 = $this->createMock(ConnectionInterface::class);

        /** @var MockObject|ConnectionInterface $connection3 */
        $connection3 = $this->createMock(ConnectionInterface::class);

        $storageId1 = 42;
        $storageId2 = 43;
        $storageId3 = 44;

        $username1 = 'user';
        $username2 = 'guest';

        /** @var MockObject|TokenInterface $client1 */
        $client1 = $this->createMock(TokenInterface::class);
        $client1->expects($this->once())
            ->method('getUsername')
            ->willReturn($username1);

        /** @var MockObject|TokenInterface $client2 */
        $client2 = $this->createMock(TokenInterface::class);
        $client2->expects($this->once())
            ->method('getUsername')
            ->willReturn($username1);

        /** @var MockObject|TokenInterface $client3 */
        $client3 = $this->createMock(TokenInterface::class);
        $client3->expects($this->once())
            ->method('getUsername')
            ->willReturn($username2);

        $this->clientStorage->expects($this->at(0))
            ->method('getStorageId')
            ->with($connection1)
            ->willReturn((string) $storageId1);

        $this->clientStorage->expects($this->at(1))
            ->method('getClient')
            ->with($storageId1)
            ->willReturn($client1);

        $this->clientStorage->expects($this->at(2))
            ->method('getStorageId')
            ->with($connection2)
            ->willReturn((string) $storageId2);

        $this->clientStorage->expects($this->at(3))
            ->method('getClient')
            ->with($storageId2)
            ->willReturn($client2);

        $this->clientStorage->expects($this->at(4))
            ->method('getStorageId')
            ->with($connection3)
            ->willReturn((string) $storageId3);

        $this->clientStorage->expects($this->at(5))
            ->method('getClient')
            ->with($storageId3)
            ->willReturn($client3);

        /** @var MockObject|Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2, $connection3]));

        $this->assertEquals(
            [
                [
                    'client' => $client1,
                    'connection' => $connection1,
                ],
                [
                    'client' => $client2,
                    'connection' => $connection2,
                ],
            ],
            $this->manipulator->findAllByUsername($topic, $username1)
        );
    }

    public function testAUserCanBeFoundByUsernameIfConnected(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $storageId = 42;
        $username = 'user';

        $client = $this->createMock(TokenInterface::class);
        $client->expects($this->once())
            ->method('getUsername')
            ->willReturn($username);

        $this->clientStorage->expects($this->once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn((string) $storageId);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->with($storageId)
            ->willReturn($client);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection]));

        $this->assertSame(
            ['client' => $client, 'connection' => $connection],
            $this->manipulator->findByUsername($topic, $username)
        );
    }

    public function testAUserCanNotBeFoundByUsernameIfNotConnected(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $storageId = 42;
        $username = 'user';

        $client = $this->createMock(AnonymousToken::class);

        $this->clientStorage->expects($this->once())
            ->method('getStorageId')
            ->with($connection)
            ->willReturn((string) $storageId);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->with($storageId)
            ->willReturn($client);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection]));

        $this->assertFalse($this->manipulator->findByUsername($topic, $username));
    }

    public function testFetchingAllConnectionsByDefaultOnlyReturnsAuthenticatedUsers(): void
    {
        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection2 = $this->createMock(ConnectionInterface::class);

        $storageId1 = 42;
        $storageId2 = 84;

        $authenticatedClient = $this->createMock(TokenInterface::class);
        $guestClient = $this->createMock(AnonymousToken::class);

        /*
         * $this->at() uses the index of all calls to the mocked object, the indexing is:
         *
         * 0: getStorageId
         * 1: getClient
         * 2: getStorageId
         * 3: getClient
         */

        $this->clientStorage->expects($this->at(0))
            ->method('getStorageId')
            ->with($connection1)
            ->willReturn((string) $storageId1);

        $this->clientStorage->expects($this->at(1))
            ->method('getClient')
            ->with($storageId1)
            ->willReturn($authenticatedClient);

        $this->clientStorage->expects($this->at(2))
            ->method('getStorageId')
            ->with($connection2)
            ->willReturn((string) $storageId2);

        $this->clientStorage->expects($this->at(3))
            ->method('getClient')
            ->with($storageId2)
            ->willReturn($guestClient);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2]));

        $this->assertSame(
            [
                ['client' => $authenticatedClient, 'connection' => $connection1],
            ],
            $this->manipulator->getAll($topic)
        );
    }

    public function testFetchingAllConnectionsWithAnonymousFlagReturnsAllConnectedUsers(): void
    {
        /** @var MockObject|ConnectionInterface $connection1 */
        $connection1 = $this->createMock(ConnectionInterface::class);

        /** @var MockObject|ConnectionInterface $connection2 */
        $connection2 = $this->createMock(ConnectionInterface::class);

        $storageId1 = 42;
        $storageId2 = 84;

        /** @var MockObject|TokenInterface $authenticatedClient */
        $authenticatedClient = $this->createMock(TokenInterface::class);

        /** @var MockObject|AnonymousToken $guestClient */
        $guestClient = $this->createMock(AnonymousToken::class);

        /*
         * $this->at() uses the index of all calls to the mocked object, the indexing is:
         *
         * 0: getStorageId
         * 1: getClient
         * 2: getStorageId
         * 3: getClient
         */

        $this->clientStorage->expects($this->at(0))
            ->method('getStorageId')
            ->with($connection1)
            ->willReturn((string) $storageId1);

        $this->clientStorage->expects($this->at(1))
            ->method('getClient')
            ->with($storageId1)
            ->willReturn($authenticatedClient);

        $this->clientStorage->expects($this->at(2))
            ->method('getStorageId')
            ->with($connection2)
            ->willReturn((string) $storageId2);

        $this->clientStorage->expects($this->at(3))
            ->method('getClient')
            ->with($storageId2)
            ->willReturn($guestClient);

        /** @var MockObject|Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2]));

        $this->assertEquals(
            [
                ['client' => $authenticatedClient, 'connection' => $connection1],
                ['client' => $guestClient, 'connection' => $connection2],
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

        $authenticatedClient1 = $this->createMock(TokenInterface::class);
        $authenticatedClient1->expects($this->once())
            ->method('getRoleNames')
            ->willReturn(['ROLE_USER', 'ROLE_STAFF']);

        $authenticatedClient2 = $this->createMock(TokenInterface::class);
        $authenticatedClient2->expects($this->once())
            ->method('getRoleNames')
            ->willReturn(['ROLE_USER']);

        $guestClient = $this->createMock(AnonymousToken::class);

        /*
         * $this->at() uses the index of all calls to the mocked object, the indexing is:
         *
         * 0: getStorageId
         * 1: getClient
         * 2: getStorageId
         * 3: getClient
         * 4: getStorageId
         * 5: getClient
         */

        $this->clientStorage->expects($this->at(0))
            ->method('getStorageId')
            ->with($connection1)
            ->willReturn((string) $storageId1);

        $this->clientStorage->expects($this->at(1))
            ->method('getClient')
            ->with($storageId1)
            ->willReturn($authenticatedClient1);

        $this->clientStorage->expects($this->at(2))
            ->method('getStorageId')
            ->with($connection2)
            ->willReturn((string) $storageId2);

        $this->clientStorage->expects($this->at(3))
            ->method('getClient')
            ->with($storageId2)
            ->willReturn($authenticatedClient2);

        $this->clientStorage->expects($this->at(4))
            ->method('getStorageId')
            ->with($connection3)
            ->willReturn((string) $storageId3);

        $this->clientStorage->expects($this->at(5))
            ->method('getClient')
            ->with($storageId3)
            ->willReturn($guestClient);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$connection1, $connection2, $connection3]));

        $this->assertSame(
            [
                ['client' => $authenticatedClient1, 'connection' => $connection1],
            ],
            $this->manipulator->findByRoles($topic, ['ROLE_STAFF'])
        );
    }
}
