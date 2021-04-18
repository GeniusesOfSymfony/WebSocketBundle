<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client;

use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ClientStorageTest extends TestCase
{
    /**
     * @var MockObject&ClearableDriverInterface
     */
    private $driver;

    /**
     * @var ClientStorage
     */
    private $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = $this->createMock(ClearableDriverInterface::class);

        $this->storage = new ClientStorage($this->driver, 10);
    }

    public function testTheClientIsRetrieved(): void
    {
        $clientId = '42';
        $token = new AnonymousToken('secret', 'anon');

        $this->driver->expects($this->once())
            ->method('fetch')
            ->with($clientId)
            ->willReturn(serialize($token));

        $this->assertEquals($token, $this->storage->getClient($clientId));
    }

    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenFetchingAClient(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Driver Gos\Bundle\WebSocketBundle\Client\ClientStorage failed');

        $clientId = '42';

        $this->driver->expects($this->once())
            ->method('fetch')
            ->with($clientId)
            ->willThrowException(new \Exception('Testing'));

        $this->storage->getClient($clientId);
    }

    public function testAnExceptionIsThrownIfTheClientIsNotFoundInStorage(): void
    {
        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage('Client 42 not found');

        $clientId = '42';

        $this->driver->expects($this->once())
            ->method('fetch')
            ->with($clientId)
            ->willReturn(false);

        $this->storage->getClient($clientId);
    }

    public function testTheStorageIdentifierOfAConnectionIsRetrieved(): void
    {
        $clientId = '42';

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = $clientId;

        $this->assertSame($clientId, $this->storage->getStorageId($connection));
    }

    public function testTheClientIsAddedToStorage(): void
    {
        $clientId = '42';
        $token = $this->createMock(TokenInterface::class);

        $this->driver->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $this->storage->addClient($clientId, $token);
    }

    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenStoringAClient(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Driver Gos\Bundle\WebSocketBundle\Client\ClientStorage failed');

        $clientId = '42';
        $token = $this->createMock(TokenInterface::class);

        $this->driver->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Testing'));

        $this->storage->addClient($clientId, $token);
    }

    public function testAnExceptionIsThrownIfTheClientIsNotAddedToStorage(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Unable to add client "user" to storage');

        $clientId = '42';

        /** @var MockObject&AbstractToken $token */
        $token = $this->createMock(AbstractToken::class);
        $token->expects($this->once())
            ->method(method_exists(AbstractToken::class, 'getUserIdentifier') ? 'getUserIdentifier' : 'getUsername')
            ->willReturn('user');

        $this->driver->expects($this->once())
            ->method('save')
            ->willReturn(false);

        $this->storage->addClient($clientId, $token);
    }

    public function testTheStorageCanBeCheckedToDetermineIfAClientExists(): void
    {
        $clientId = '42';

        $this->driver->expects($this->once())
            ->method('contains')
            ->willReturn(true);

        $this->assertTrue($this->storage->hasClient($clientId));
    }

    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenCheckingForPresence(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Driver Gos\Bundle\WebSocketBundle\Client\ClientStorage failed');

        $clientId = '42';

        $this->driver->expects($this->once())
            ->method('contains')
            ->willThrowException(new \Exception('Testing'));

        $this->storage->hasClient($clientId);
    }

    public function testAClientCanBeRemovedFromStorage(): void
    {
        $clientId = '42';

        $this->driver->expects($this->once())
            ->method('delete')
            ->willReturn(true);

        $this->assertTrue($this->storage->removeClient($clientId));
    }

    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenRemovingAClient(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Driver Gos\Bundle\WebSocketBundle\Client\ClientStorage failed');

        $clientId = '42';

        $this->driver->expects($this->once())
            ->method('delete')
            ->willThrowException(new \Exception('Testing'));

        $this->storage->removeClient($clientId);
    }

    public function testAllClientsCanBeRemovedFromStorage(): void
    {
        $this->driver->expects($this->once())
            ->method('clear');

        $this->storage->removeAllClients();
    }

    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenRemovingAllClients(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Driver Gos\Bundle\WebSocketBundle\Client\ClientStorage failed');

        $this->driver->expects($this->once())
            ->method('clear')
            ->willThrowException(new \Exception('Testing'));

        $this->storage->removeAllClients();
    }
}

interface ClearableDriverInterface extends DriverInterface
{
    public function clear(): void;
}
