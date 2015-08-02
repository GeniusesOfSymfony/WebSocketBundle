<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ZmqPusher extends AbstractPusher
{
    /**
     * @param string $data
     */
    protected function doPush($data, array $context)
    {
        $config = $this->getConfig();

        if(false === $this->isConnected()){
            $resolver = new OptionsResolver();

            $resolver->setDefaults([
                'persistent' => false,
                'protocol' => 'tcp',
            ]);

            $resolver->setAllowedTypes([
                'persistent' => ['bool'],
                'protocol' => ['string'],
            ]);

            $resolver->setAllowedValues([
                'protocol' => ['tcp', 'ipc', 'inproc', 'pgm', 'epgm'],
            ]);

            $options = $resolver->resolve($config['options']);

            $context = new \ZMQContext(1, $options['persistent']);
            $this->connection = new \ZMQSocket($context, \ZMQ::SOCKET_PUSH);
            $this->connection->connect($options['protocol'].'://'.$config['host'].':'.$config['port']);
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

        $this->connection->disconnect($config['host'].':'.$config['port']);
    }
}
