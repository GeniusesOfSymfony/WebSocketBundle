<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class RpcRegistry
{
    /**
     * @var RpcInterface[]
     */
    protected $rpcHandlers;

    public function __construct()
    {
        $this->rpcHandlers = array();
    }

    /**
     * @param RpcInterface $rpcHandler
     */
    public function addRpc(RpcInterface $rpcHandler)
    {
        $this->rpcHandlers[$rpcHandler->getPrefix()] = $rpcHandler;
    }

    /**
     * @param string $name
     *
     * @return RpcInterface
     * @throws \Exception
     */
    public function getRpc($name)
    {
        if (isset($this->rpcHandlers[$name])) {
            return $this->rpcHandlers[$name];
        }

        throw new \Exception(sprintf('rpc handler %s not exists, only [ %s ] are available',
            $name,
            implode(', ', array_keys($this->rpcHandlers))
        ));
    }
}
