<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Client;

use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ClientStorageTest extends TestCase
{
    /**
     * @var MockObject|DriverInterface
     */
    private $driver;

    /**
     * @var ClientStorage
     */
    private $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = $this->createMock(DriverInterface::class);

        $this->storage = new ClientStorage(10);
        $this->storage->setStorageDriver($this->driver);
    }

    public function testTheClientIsRetrieved()
    {
        $clientId = '42';
        $token = new AnonymousToken('secret', 'anon');

        $this->driver->expects($this->once())
            ->method('fetch')
            ->with($clientId)
            ->willReturn(serialize($token));

        $this->assertEquals($token, $this->storage->getClient($clientId));
    }

    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenFetchingAClient()
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

    public function testAnExceptionIsThrownIfTheClientIsNotFoundInStorage()
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

    public function testTheStorageIdentifierOfAConnectionIsRetrieved()
    {
        $clientId = '42';

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = $clientId;

        $this->assertSame($clientId, $this->storage->getStorageId($connection));
    }

    public function testTheClientIsAddedToStorage()
    {
        $clientId = '42';
        $token = $this->createMock(TokenInterface::class);

        $this->driver->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $this->storage->addClient($clientId, $token);
    }

    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenStoringAClient()
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

    public function testAnExceptionIsThrownIfTheClientIsNotAddedToStorage()
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Unable to add client "user" to storage');

        $clientId = '42';
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUsername')
            ->willReturn('user');

        $this->driver->expects($this->once())
            ->method('save')
            ->willReturn(false);

        $this->storage->addClient($clientId, $token);
    }

    public function testTheStorageCanBeCheckedToDetermineIfAClientExists()
    {
        $clientId = '42';

        $this->driver->expects($this->once())
            ->method('contains')
            ->willReturn(true);

        $this->assertTrue($this->storage->hasClient($clientId));
    }

    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenCheckingForPresence()
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Driver Gos\Bundle\WebSocketBundle\Client\ClientStorage failed');

        $clientId = '42';

        $this->driver->expects($this->once())
            ->method('contains')
            ->willThrowException(new \Exception('Testing'));

        $this->storage->hasClient($clientId);
    }

    public function testAClientCanBeRemovedFromStorage()
    {
        $clientId = '42';

        $this->driver->expects($this->once())
            ->method('delete')
            ->willReturn(true);

        $this->assertTrue($this->storage->removeClient($clientId));
    }

    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenRemovingAClient()
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Driver Gos\Bundle\WebSocketBundle\Client\ClientStorage failed');

        $clientId = '42';

        $this->driver->expects($this->once())
            ->method('delete')
            ->willThrowException(new \Exception('Testing'));

        $this->storage->removeClient($clientId);
    }
}
