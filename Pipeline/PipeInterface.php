<?php

namespace Gos\Bundle\WebSocketBundle\Pipeline;

use Ratchet\ConnectionInterface;

interface PipeInterface
{
    /**
     * @param ConnectionInterface $connection
     *
     * @return $this
     */
    public function connection(ConnectionInterface $connection);

    /**
     * @param string      $routeName
     * @param array $attributes
     *
     * @return $this
     */
    public function request($routeName, array $attributes = array());

    /**
     * @param string $data
     *
     * @return $this
     */
    public function data($data);

    /**
     * @param string $forward
     *
     * @return $this
     * @throws \Exception
     */
    public function forward($forward);

    /**
     * @return ConnectionInterface
     */
    public function getConnection();

    /**
     * @return array
     */
    public function getRequestData();

    /**
     * @return string
     */
    public function getData();

    /**
     * @return string
     */
    public function getForward();

    /**
     * @return \string[]
     */
    public function getEligible();

    /**
     * @return \string[]
     */
    public function getExclude();

    /**
     * @param array $exclude
     *
     * @return $this
     */
    public function exclude($exclude = array());

    /**
     * @param array $eligible
     *
     * @return $this
     */
    public function eligible($eligible = array());
}