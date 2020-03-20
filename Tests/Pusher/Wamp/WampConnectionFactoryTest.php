<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher\Wamp;

use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampConnectionFactory;
use Gos\Component\WebSocketClient\Wamp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class WampConnectionFactoryTest extends TestCase
{
    public function dataInvalidConfiguration(): \Generator
    {
        yield 'host as a number' => [
            [
                'host' => 42,
                'port' => 1337,
            ],
            InvalidOptionsException::class,
        ];

        yield 'host missing' => [
            [
                'port' => 1337,
            ],
            MissingOptionsException::class,
            'The required option "host" is missing.',
        ];
    }

    public function dataValidConfiguration(): \Generator
    {
        yield 'filling in missing required parameters' => [
            [
                'host' => 'localhost',
                'port' => 1337,
            ],
        ];

        yield 'configuring all parameters' => [
            [
                'host' => 'localhost',
                'port' => 1337,
                'ssl' => true,
                'origin' => 'localhost',
            ],
        ];
    }

    /**
     * @dataProvider dataValidConfiguration
     */
    public function testTheFactoryIsCreatedWithAValidConfiguration(array $config): void
    {
        $this->assertInstanceOf(WampConnectionFactory::class, new WampConnectionFactory($config));
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     *
     * @dataProvider dataInvalidConfiguration
     */
    public function testTheFactoryIsNotCreatedWithAnInvalidConfiguration(
        array $config,
        string $exceptionClass,
        ?string $exceptionMessage = null
    ): void {
        $this->expectException($exceptionClass);

        if (null !== $exceptionMessage) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $this->assertInstanceOf(WampConnectionFactory::class, new WampConnectionFactory($config));
    }

    public function testTheConnectionObjectIsCreated(): void
    {
        $config = [
            'host' => 'localhost',
            'port' => 1337,
        ];

        $connection = (new WampConnectionFactory($config))->createConnection();

        $this->assertInstanceOf(ClientInterface::class, $connection);
    }
}
