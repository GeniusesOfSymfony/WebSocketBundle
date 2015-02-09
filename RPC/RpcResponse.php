<?php

namespace Gos\Bundle\WebSocketBundle\RPC;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class RpcResponse
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param array  $data
     * @param string $prefix
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @param mixed  $data
     * @param string $prefix
     */
    public function setData($key, $data, $prefix = 'result')
    {
        $this->data[$prefix][$key] = $data;
    }

    /**
     * @param mixed  $data
     * @param string $prefix
     */
    public function addData($data, $prefix = 'result')
    {
        $this->data[$prefix] = array_combine($this->data[$prefix], $data);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
