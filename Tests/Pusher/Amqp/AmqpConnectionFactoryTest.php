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
                'port' => 1337,
                'login' => 'foo',
                'password' => 'foo',
            ],
            InvalidOptionsException::class,
            'The option "host" with value 42 is expected to be of type "string", but is of type "integer".',
        ];

        yield 'host missing' => [
            [
                'port' => 1337,
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
                'port' => 1337,
                'login' => 'foo',
                'password' => 'foo',
            ],
        ];

        yield 'configuring all parameters' => [
            [
                'host' => 'localhost',
                'port' => 1337,
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
    public function testTheFactoryIsCreatedWithAValidConfiguration(array $config)
    {
        $this->assertInstanceOf(AmqpConnectionFactory::class, new AmqpConnectionFactory($config));
    }

    /**
     * @dataProvider dataInvalidConfiguration
     */
    public function testTheFactoryIsNotCreatedWithAnInvalidConfiguration(
        array $config,
        string $exceptionClass,
        string $exceptionMessage
    ) {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);

        $this->assertInstanceOf(AmqpConnectionFactory::class, new AmqpConnectionFactory($config));
    }

    /**
     * @requires extension amqp
     */
    public function testTheConnectionObjectIsCreated()
    {
        $config = [
            'host' => 'localhost',
            'port' => 1337,
            'login' => 'foo',
            'password' => 'foo',
        ];

        $connection = (new AmqpConnectionFactory($config))->createConnection();

        $this->assertInstanceOf(\AMQPConnection::class, $connection);
    }
}
