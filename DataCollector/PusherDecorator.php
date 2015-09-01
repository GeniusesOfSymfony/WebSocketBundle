<?php

namespace Gos\Bundle\WebSocketBundle\DataCollector;

use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\PubSubRouterBundle\Request\PubSubRequest;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * This class is only put in front of Pusher in dev env
 */
class PusherDecorator implements PusherInterface
{
    /** @var  PusherInterface */
    protected $pusher;

    /**
     * Useful when you debug to directly see the decorated class
     * @var  string
     */
    protected $decoratedClass;

    /** @var  Stopwatch */
    protected $stopwatch;

    /**
     * @param PusherInterface $pusher
     * @param Stopwatch $stopwatch
     */
    public function __construct(PusherInterface $pusher, Stopwatch $stopwatch)
    {
        $this->pusher = $pusher;
        $this->stopwatch = $stopwatch;
        $this->decoratedClass = get_class($pusher);
    }

    /**
     * @param string|array $data
     * @param string       $routeName
     * @param array[]      $routeParameters
     */
    public function push($data, $routeName, Array $routeParameters = array(), Array $context = [])
    {
        $eventName = 'push.'.$this->getName();
        $this->stopwatch->start($eventName, 'websocket');
        $this->pusher->push($data, $routeName, $routeParameters, $context);
        $this->stopwatch->stop($eventName);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->pusher->getConfig();
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->pusher->setConfig($config);
    }

    public function close()
    {
        $this->pusher->close();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->pusher->getName();
    }
}
