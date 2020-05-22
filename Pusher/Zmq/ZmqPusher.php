<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;

trigger_deprecation('gos/web-socket-bundle', '1.10', 'The %s class is deprecated will be removed in 2.0.', ZmqPusher::class);

/**
 * @deprecated to be removed in 2.0
 */
class ZmqPusher extends AbstractPusher
{
    /**
     * @param string $data
     * @param array  $context
     */
    protected function doPush($data, array $context)
    {
        if (false === $this->isConnected()) {
            if (!extension_loaded('zmq')) {
                throw new \RuntimeException(sprintf(
                    '%s pusher require %s php extension',
                    get_class($this),
                    $this->getName()
                ));
            }

            $config = $this->getConfig();

            $context = new \ZMQContext(1, $config['persistent']);
            $this->connection = new \ZMQSocket($context, \ZMQ::SOCKET_PUSH);
            $this->connection->setSockOpt(\ZMQ::SOCKOPT_LINGER, $config['linger']);
            $this->connection->connect($config['protocol'] . '://' . $config['host'] . ':' . $config['port']);
            $this->setConnected();
        }

        $this->connection->send($data);
    }

    public function close()
    {
        if (false === $this->isConnected()) {
            return;
        }

        $config = $this->getConfig();

        $this->connection->disconnect($config['host'] . ':' . $config['port']);
    }
}
