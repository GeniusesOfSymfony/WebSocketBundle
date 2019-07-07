<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use React\EventLoop\LoopInterface;
use React\ZMQ\Context;
use React\ZMQ\SocketWrapper;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ZmqConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $this->resolveConfig($config);
    }

    public function buildConnectionDsn(): string
    {
        return $this->config['protocol'].'://'.$this->config['host'].':'.$this->config['port'];
    }

    public function createConnection(): \ZMQSocket
    {
        if (!$this->isSupported()) {
            throw new \RuntimeException('The ZMQ pusher requires the PHP zmq extension.');
        }

        $context = new \ZMQContext(1, $this->config['persistent']);

        $connection = $context->getSocket(\ZMQ::SOCKET_PUSH);
        $connection->setSockOpt(\ZMQ::SOCKOPT_LINGER, $this->config['linger']);

        return $connection;
    }

    public function createWrappedConnection(LoopInterface $loop, int $socketType = 7 /*\ZMQ::SOCKET_PULL*/): SocketWrapper
    {
        if (!$this->isSupported()) {
            throw new \RuntimeException('The ZMQ pusher requires the PHP zmq extension.');
        }

        if (!class_exists(Context::class)) {
            throw new \RuntimeException('The ZMQ pusher requires the react/zmq package to create a wrapped connection.');
        }

        $context = new Context($loop, new \ZMQContext(1, $this->config['persistent']));

        $connection = $context->getSocket($socketType);
        $connection->setSockOpt(\ZMQ::SOCKOPT_LINGER, $this->config['linger']);

        return $connection;
    }

    public function isSupported(): bool
    {
        return \extension_loaded('zmq');
    }

    private function resolveConfig(array $config): array
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired(
            [
                'persistent',
                'host',
                'port',
                'protocol',
                'linger',
            ]
        );

        $resolver->setDefaults(
            [
                'persistent' => true,
                'protocol' => 'tcp',
                'linger' => -1,
            ]
        );

        $resolver->setAllowedTypes('persistent', 'boolean');
        $resolver->setAllowedTypes('host', 'string');
        $resolver->setAllowedTypes('port', ['string', 'integer']);
        $resolver->setAllowedTypes('protocol', 'string');
        $resolver->setAllowedTypes('linger', 'integer');

        return $resolver->resolve($config);
    }
}
