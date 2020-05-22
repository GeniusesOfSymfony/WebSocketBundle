<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;

abstract class AbstractPusher implements PusherInterface
{
    /**
     * @var MessageSerializer
     */
    protected $serializer;

    /**
     * @var array
     * @deprecated to be removed in 2.0. Configuration will no longer be automatically injected in pushers.
     */
    private $config;

    /**
     * @var WampRouter
     */
    protected $router;

    /**
     * @var bool
     */
    protected $connected = false;

    protected $connection;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $data
     * @param array  $context
     *
     * @return string
     */
    abstract protected function doPush($data, array $context);

    /**
     * @param MessageSerializer $serializer
     */
    public function setSerializer(MessageSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param WampRouter $router
     */
    public function setRouter(WampRouter $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig($config)
    {
        trigger_deprecation('gos/web-socket-bundle', '1.10', 'The %s() method is deprecated will be removed in 2.0. Configuration will no longer be automatically injected in pushers.', __METHOD__);

        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        trigger_deprecation('gos/web-socket-bundle', '1.10', 'The %s() method is deprecated will be removed in 2.0. Configuration will no longer be automatically injected in pushers.', __METHOD__);

        return $this->config;
    }

    /**
     * @param bool|true $bool
     */
    public function setConnected($bool = true)
    {
        $this->connected = $bool;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * @param array|string $data
     * @param string       $routeName
     * @param array[]      $routeParameters
     * @param array        $context
     *
     * @return string|\Symfony\Component\Serializer\Encoder\scalar
     */
    public function push($data, $routeName, Array $routeParameters = array(), Array $context = [])
    {
        $channel = $this->router->generate($routeName, $routeParameters);
        $message = new Message($channel, $data);

        return $this->doPush($this->serializer->serialize($message), $context);
    }
}
