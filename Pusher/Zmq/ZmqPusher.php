<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\MessageInterface;
use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ZmqPusher implements PusherInterface
{
    /** @var  resource */
    protected $connection;

    /** @var  array */
    protected $pusherConfig;

    /** @var bool  */
    protected $isConnected = false;

    /** @var  \ZMQSocket */
    protected $client;

    /** @var  WampRouter */
    protected $router;

    /**
     * @param WampRouter $router
     * @param array      $pusherConfig
     */
    public function __construct(WampRouter $router, Array $pusherConfig)
    {
        $this->pusherConfig = $pusherConfig;
        $this->router = $router;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->pusherConfig;
    }

    /**
     * @param MessageInterface $data
     * @param string           $routeName
     * @param array[]          $routeParameters
     */
    public function push($data, $routeName, $routeParameters)
    {
        if (false === $this->isConnected) {
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

            $options = $resolver->resolve($this->pusherConfig['options']);

            $context = new \ZMQContext(1, $options['persistent']);
            $this->client = new \ZMQSocket($context, \ZMQ::SOCKET_PUSH);
            $this->client->connect($options['protocol'].'://'.$this->pusherConfig['host'].':'.$this->pusherConfig['port']);
        }

        $chanel = $this->router->generate($routeName, $routeParameters);

        $this->client->send(json_encode([
            'topic' => $chanel,
            'data' => $data,
        ]));
    }

    public function close()
    {
        if (false === $this->isConnected) {
            return;
        }

        $this->client->disconnect($this->pusherConfig['host'].':'.$this->pusherConfig['port']);
    }
}
