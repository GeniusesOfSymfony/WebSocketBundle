<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;

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
