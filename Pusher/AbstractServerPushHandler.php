<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

abstract class AbstractServerPushHandler implements ServerPushHandlerInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     * @deprecated to be removed in 2.0. Configuration will no longer be automatically injected in server push handlers.
     */
    private $config;

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        @trigger_error(
            sprintf('%s() method is deprecated will be removed in 2.0. Configuration will no longer be automatically injected in pushers.', __METHOD__),
            E_USER_DEPRECATED
        );

        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config)
    {
        @trigger_error(
            sprintf('%s() method is deprecated will be removed in 2.0. Configuration will no longer be automatically injected in pushers.', __METHOD__),
            E_USER_DEPRECATED
        );

        $this->config = $config;
    }
}
