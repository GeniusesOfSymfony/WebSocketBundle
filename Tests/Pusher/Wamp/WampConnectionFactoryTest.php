<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher\Wamp;

use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampConnectionFactory;
use Gos\Component\WebSocketClient\Wamp\Client;
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
            'The option "host" with value 42 is expected to be of type "string", but is of type "integer".',
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
    public function testTheFactoryIsCreatedWithAValidConfiguration(array $config)
    {
        $this->assertInstanceOf(WampConnectionFactory::class, new WampConnectionFactory($config));
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

        $this->assertInstanceOf(WampConnectionFactory::class, new WampConnectionFactory($config));
    }

    public function testTheConnectionObjectIsCreated()
    {
        $config = [
            'host' => 'localhost',
            'port' => 1337,
        ];

        $connection = (new WampConnectionFactory($config))->createConnection();

        $this->assertInstanceOf(Client::class, $connection);
    }
}
