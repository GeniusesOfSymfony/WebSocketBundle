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

    /** @var  WebsocketDataCollector */
    protected $dataCollector;

    /**
     * @param PusherInterface $pusher
     * @param Stopwatch $stopwatch
     * @param WebsocketDataCollector $dataCollector
     */
    public function __construct(PusherInterface $pusher, Stopwatch $stopwatch, WebsocketDataCollector $dataCollector)
    {
        $this->pusher = $pusher;
        $this->stopwatch = $stopwatch;
        $this->decoratedClass = get_class($pusher);
        $this->dataCollector = $dataCollector;
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
        $this->dataCollector->collectData($this->stopwatch->getEvent($eventName), $this->getName());
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
