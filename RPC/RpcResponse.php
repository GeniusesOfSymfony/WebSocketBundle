<?php

namespace Gos\Bundle\WebSocketBundle\RPC;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class RpcResponse
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param mixed $data
     */
    public function __construct($data, string $prefix = 'result')
    {
        $this->data[$prefix] = $data;
    }

    /**
     * @param mixed $data
     */
    public function setData(string $key, $data, string $prefix = 'result'): void
    {
        $this->data[$prefix][$key] = $data;
    }

    /**
     * @param mixed $data
     */
    public function addData($data, string $prefix = 'result'): void
    {
        $this->data[$prefix] = array_combine($this->data[$prefix], $data);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
