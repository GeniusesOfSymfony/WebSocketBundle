<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Authentication\Storage;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver\StorageDriverInterface;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class TokenStorageTest extends TestCase
{
    /**
     * @var MockObject&StorageDriverInterface
     */
    private $driver;

    /**
     * @var TokenStorage
     */
    private $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = $this->createMock(StorageDriverInterface::class);

        $this->storage = new TokenStorage($this->driver);
    }

    public function testAStorageIdentifierForAConnectionIsGenerated(): void
    {
        $clientId = '42';

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = $clientId;

        self::assertSame($clientId, $this->storage->generateStorageId($connection));
    }

    public function testTheTokenIsAddedToStorage(): void
    {
        /** @var MockObject&TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);

        $this->driver->expects(self::once())
            ->method('store')
            ->willReturn(true);

        $this->storage->addToken('42', $token);
    }

    public function testAnExceptionIsThrownIfTheTokenIsNotAddedToStorage(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Unable to add client "user" to storage');

        /** @var MockObject&AbstractToken $token */
        $token = $this->createMock(AbstractToken::class);
        $token->expects(self::once())
            ->method(method_exists(AbstractToken::class, 'getUserIdentifier') ? 'getUserIdentifier' : 'getUsername')
            ->willReturn('user');

        $this->driver->expects(self::once())
            ->method('store')
            ->willReturn(false);

        $this->storage->addToken('42', $token);
    }

    public function testTheTokenIsRetrieved(): void
    {
        $storageId = '42';

        /** @var MockObject&TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);

        $this->driver->expects(self::once())
            ->method('get')
            ->with($storageId)
            ->willReturn($token);

        self::assertEquals($token, $this->storage->getToken($storageId));
    }

    public function testTheStorageCanBeCheckedToDetermineIfATokenExists(): void
    {
        $this->driver->expects(self::once())
            ->method('has')
            ->willReturn(true);

        self::assertTrue($this->storage->hasToken('42'));
    }

    public function testATokenCanBeRemovedFromStorage(): void
    {
        $this->driver->expects(self::once())
            ->method('delete')
            ->willReturn(true);

        self::assertTrue($this->storage->removeToken('42'));
    }

    public function testAllTokensCanBeRemovedFromStorage(): void
    {
        $this->driver->expects(self::once())
            ->method('clear');

        $this->storage->removeAllTokens();
    }
}
