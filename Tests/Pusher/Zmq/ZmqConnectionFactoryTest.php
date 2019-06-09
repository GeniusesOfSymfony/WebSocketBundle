<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\Zmq\ZmqConnectionFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ZmqConnectionFactoryTest extends TestCase
{
    public function dataInvalidConfiguration(): \Generator
    {
        yield 'port as a string' => [
            [
                'host' => 'localhost',
                'port' => '1337',
            ],
            InvalidOptionsException::class,
            'The option "port" with value "1337" is expected to be of type "integer", but is of type "string".',
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
                'persistent' => false,
                'host' => 'localhost',
                'port' => 1337,
                'protocol' => 'udp',
                'linger' => 42,
            ],
        ];
    }

    /**
     * @dataProvider dataValidConfiguration
     */
    public function testTheFactoryIsCreatedWithAValidConfiguration(array $config)
    {
        $this->assertInstanceOf(ZmqConnectionFactory::class, new ZmqConnectionFactory($config));
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

        $this->assertInstanceOf(ZmqConnectionFactory::class, new ZmqConnectionFactory($config));
    }

    public function testTheConnectionDsnIsBuilt()
    {
        $config = [
            'host' => 'localhost',
            'port' => 1337,
        ];

        $this->assertSame('tcp://localhost:1337', (new ZmqConnectionFactory($config))->buildConnectionDsn());
    }

    /**
     * @requires extension zmq
     */
    public function testTheConnectionObjectIsCreated()
    {
        $config = [
            'host' => 'localhost',
            'port' => 1337,
        ];

        $connection = (new ZmqConnectionFactory($config))->createConnection();

        $this->assertInstanceOf(\ZMQSocket::class, $connection);
        $this->assertSame(-1, $connection->getSockOpt(\ZMQ::SOCKOPT_LINGER));
    }
}
