<?php

namespace Gos\Bundle\WebSocketBundle\DataCollector;

use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\PubSubRouterBundle\Request\PubSubRequest;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Pusher decorating another Pusher to collect data
 */
class PusherDecorator implements PusherInterface
{
    /**
     * @var PusherInterface
     */
    protected $pusher;

    /**
     * Class name of the Pusher which was decorated
     *
     * @var string
     */
    protected $decoratedClass;

    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    /**
     * @var WebsocketDataCollector
     */
    protected $dataCollector;

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
     * @param array        $routeParameters
     * @param array        $context
     */
    public function push($data, $routeName, array $routeParameters = [], array $context = [])
    {
        $eventName = 'push.'.$this->getName();

        $this->stopwatch->start($eventName, 'websocket');
        $this->pusher->push($data, $routeName, $routeParameters, $context);
        $this->stopwatch->stop($eventName);

        $this->dataCollector->collectData($this->stopwatch->getEvent($eventName), $this->getName());
    }

    public function close()
    {
        $this->pusher->close();
    }

    public function setName(string $name): void
    {
        $this->pusher->setName($name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->pusher->getName();
    }
}
