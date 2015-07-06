<?php

namespace Gos\Bundle\WebSocketBundle\Pipeline;

use Ratchet\ConnectionInterface;

class Pipe implements PipeInterface
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var array
     */
    protected $requestData;

    /**
     * @var string
     */
    protected $data;

    /**
     * @var string
     */
    protected $forward;

    /**
     * @var string[]
     */
    protected $eligible;

    /**
     * @var string[]
     */
    protected $exclude;

    /**
     * @param ConnectionInterface $connection
     *
     * @return $this
     */
    public function connection(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param array $exclude
     *
     * @return $this
     */
    public function exclude($exclude = array())
    {
        $this->exclude = (array) $exclude;

        return $this;
    }

    /**
     * @param array $eligible
     *
     * @return $this
     */
    public function eligible($eligible = array())
    {
        $this->eligible = (array) $eligible;

        return $this;
    }

    /**
     * @param string      $routeName
     * @param array $attributes
     *
     * @return $this
     */
    public function request($routeName, array $attributes = array())
    {
        $this->requestData = [$routeName, $attributes];

        return $this;
    }

    /**
     * @param string $data
     *
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param string $forward
     *
     * @return $this
     * @throws \Exception
     */
    public function forward($forward)
    {
        if(!in_array($forward, $possibility = [
            WampPipelineInterface::PUBLICATION,
            WampPipelineInterface::SUBSCRIPTION,
            WampPipelineInterface::UNSUBSCRIPTION
        ])){
            throw new \Exception(sprintf(
                'You can\'t forward %s, only [ %s ] is possible',
                $forward,
                implode(', ', $possibility)
            ));
        }

        $this->forward = $forward;

        return $this;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getForward()
    {
        return $this->forward;
    }

    /**
     * @return \string[]
     */
    public function getEligible()
    {
        return $this->eligible;
    }

    /**
     * @return \string[]
     */
    public function getExclude()
    {
        return $this->exclude;
    }
}