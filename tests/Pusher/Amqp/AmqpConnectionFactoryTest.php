<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Pusher\Amqp\AmqpConnectionFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class AmqpConnectionFactoryTest extends TestCase
{
    public function dataInvalidConfiguration(): \Generator
    {
        yield 'host as a number' => [
            [
                'host' => 42,
                'port' => 5672,
                'login' => 'foo',
                'password' => 'foo',
            ],
            InvalidOptionsException::class,
        ];

        yield 'host missing' => [
            [
                'port' => 5672,
                'login' => 'foo',
                'password' => 'foo',
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
                'port' => 5672,
                'login' => 'foo',
                'password' => 'foo',
            ],
        ];

        yield 'configuring all parameters' => [
            [
                'host' => 'localhost',
                'port' => 5672,
                'login' => 'foo',
                'password' => 'foo',
                'vhost' => '/',
                'read_timeout' => 42,
                'write_timeout' => 42,
                'connect_timeout' => 42,
                'queue_name' => 'websocket',
                'exchange_name' => 'websocket_exchange',
            ],
        ];
    }

    /**
     * @dataProvider dataValidConfiguration
     */
    public function testTheFactoryIsCreatedWithAValidConfiguration(array $config): void
    {
        self::assertInstanceOf(AmqpConnectionFactory::class, new AmqpConnectionFactory($config));
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

        self::assertInstanceOf(AmqpConnectionFactory::class, new AmqpConnectionFactory($config));
    }

    /**
     * @requires extension amqp
     */
    public function testTheConnectionObjectIsCreated(): void
    {
        $config = [
            'host' => 'localhost',
            'port' => 5672,
            'login' => 'foo',
            'password' => 'foo',
        ];

        $connection = (new AmqpConnectionFactory($config))->createConnection();

        self::assertInstanceOf(\AMQPConnection::class, $connection);
    }
}
