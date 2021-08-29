<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Authentication;

use Gos\Bundle\WebSocketBundle\Authentication\Authenticator;
use Gos\Bundle\WebSocketBundle\Authentication\Provider\AuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class AuthenticatorTest extends TestCase
{
    public function testTheAuthenticatorDoesNotAuthenticateAConnectionWhenItHasNoProviders(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        /** @var MockObject&TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::never())
            ->method('generateStorageId');

        $tokenStorage->expects(self::never())
            ->method('addToken');

        (new Authenticator([], $tokenStorage))->authenticate($connection);
    }

    public function testTheAuthenticatorAuthenticatesAConnectionWhenItHasOneProvider(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        /** @var MockObject&TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);

        /** @var MockObject&TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn('conn-123');

        $tokenStorage->expects(self::once())
            ->method('addToken')
            ->with('conn-123', $token);

        /** @var MockObject&AuthenticationProviderInterface $authenticationProvider */
        $authenticationProvider = $this->createMock(AuthenticationProviderInterface::class);
        $authenticationProvider->expects(self::once())
            ->method('supports')
            ->with($connection)
            ->willReturn(true);

        $authenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($connection)
            ->willReturn($token);

        (new Authenticator([$authenticationProvider], $tokenStorage))->authenticate($connection);
    }

    public function testTheAuthenticatorAuthenticatesAConnectionUsingTheFirstSupportedProvider(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        /** @var MockObject&TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);

        /** @var MockObject&TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())
            ->method('generateStorageId')
            ->with($connection)
            ->willReturn('conn-123');

        $tokenStorage->expects(self::once())
            ->method('addToken')
            ->with('conn-123', $token);

        /** @var MockObject&AuthenticationProviderInterface $authenticationProvider1 */
        $authenticationProvider1 = $this->createMock(AuthenticationProviderInterface::class);
        $authenticationProvider1->expects(self::once())
            ->method('supports')
            ->with($connection)
            ->willReturn(false);

        $authenticationProvider1->expects(self::never())
            ->method('authenticate');

        /** @var MockObject&AuthenticationProviderInterface $authenticationProvider2 */
        $authenticationProvider2 = $this->createMock(AuthenticationProviderInterface::class);
        $authenticationProvider2->expects(self::once())
            ->method('supports')
            ->with($connection)
            ->willReturn(true);

        $authenticationProvider2->expects(self::once())
            ->method('authenticate')
            ->with($connection)
            ->willReturn($token);

        /** @var MockObject&AuthenticationProviderInterface $authenticationProvider3 */
        $authenticationProvider3 = $this->createMock(AuthenticationProviderInterface::class);
        $authenticationProvider3->expects(self::never())
            ->method('supports');

        $authenticationProvider3->expects(self::never())
            ->method('authenticate');

        (new Authenticator([$authenticationProvider1, $authenticationProvider2, $authenticationProvider3], $tokenStorage))->authenticate($connection);
    }
}
