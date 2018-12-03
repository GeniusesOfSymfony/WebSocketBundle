<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Client;

use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;

class ClientStorageTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    private $driver;

    /**
     * @var ClientStorage
     */
    private $storage;

    protected function setUp()
    {
        parent::setUp();

        $this->driver = $this->createMock(DriverInterface::class);

        $this->storage = new ClientStorage(10);
        $this->storage->setStorageDriver($this->driver);
    }

    public function testTheClientIsRetrieved()
    {
        $clientId = 42;
        $username = 'user';

        $this->driver->expects($this->once())
            ->method('fetch')
            ->with($clientId)
            ->willReturn(serialize($username));

        $this->assertSame($username, $this->storage->getClient($clientId));
    }

    /**
     * @expectedException Gos\Bundle\WebSocketBundle\Client\Exception\StorageException
     * @expectedExceptionMessage Driver Gos\Bundle\WebSocketBundle\Client\ClientStorage failed
     */
    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenFetchingAClient()
    {
        $clientId = 42;

        $this->driver->expects($this->once())
            ->method('fetch')
            ->with($clientId)
            ->willThrowException(new \Exception('Testing'));

        $this->storage->getClient($clientId);
    }

    /**
     * @expectedException Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException
     * @expectedExceptionMessage Client 42 not found
     */
    public function testAnExceptionIsThrownIfTheClientIsNotFoundInStorage()
    {
        $clientId = 42;

        $this->driver->expects($this->once())
            ->method('fetch')
            ->with($clientId)
            ->willReturn(false);

        $this->storage->getClient($clientId);
    }

    public function testTheStorageIdentifierOfAConnectionIsRetrieved()
    {
        $clientId = 42;

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = $clientId;

        $this->assertSame($clientId, $this->storage->getStorageId($connection));
    }

    public function testTheClientIsAddedToStorage()
    {
        $clientId = 42;
        $username = 'user';

        $this->driver->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $this->storage->addClient($clientId, $username);
    }

    /**
     * @expectedException Gos\Bundle\WebSocketBundle\Client\Exception\StorageException
     * @expectedExceptionMessage Driver Gos\Bundle\WebSocketBundle\Client\ClientStorage failed
     */
    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenStoringAClient()
    {
        $clientId = 42;
        $username = 'user';

        $this->driver->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Testing'));

        $this->storage->addClient($clientId, $username);
    }

    /**
     * @expectedException Gos\Bundle\WebSocketBundle\Client\Exception\StorageException
     * @expectedExceptionMessage Unable to add client "user" to storage
     */
    public function testAnExceptionIsThrownIfTheClientIsNotAddedToStorage()
    {
        $clientId = 42;
        $username = 'user';

        $this->driver->expects($this->once())
            ->method('save')
            ->willReturn(false);

        $this->storage->addClient($clientId, $username);
    }

    public function testTheStorageCanBeCheckedToDetermineIfAClientExists()
    {
        $clientId = 42;

        $this->driver->expects($this->once())
            ->method('contains')
            ->willReturn(true);

        $this->assertTrue($this->storage->hasClient($clientId));
    }

    /**
     * @expectedException Gos\Bundle\WebSocketBundle\Client\Exception\StorageException
     * @expectedExceptionMessage Driver Gos\Bundle\WebSocketBundle\Client\ClientStorage failed
     */
    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenCheckingForPresence()
    {
        $clientId = 42;

        $this->driver->expects($this->once())
            ->method('contains')
            ->willThrowException(new \Exception('Testing'));

        $this->storage->hasClient($clientId);
    }

    public function testAClientCanBeRemovedFromStorage()
    {
        $clientId = 42;

        $this->driver->expects($this->once())
            ->method('delete')
            ->willReturn(true);

        $this->assertTrue($this->storage->removeClient($clientId));
    }

    /**
     * @expectedException Gos\Bundle\WebSocketBundle\Client\Exception\StorageException
     * @expectedExceptionMessage Driver Gos\Bundle\WebSocketBundle\Client\ClientStorage failed
     */
    public function testAnExceptionIsThrownIfTheStorageDriverFailsWhenRemovingAClient()
    {
        $clientId = 42;

        $this->driver->expects($this->once())
            ->method('delete')
            ->willThrowException(new \Exception('Testing'));

        $this->storage->removeClient($clientId);
    }
}
