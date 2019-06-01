<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class RpcRegistry
{
    /**
     * @var RpcInterface[]
     */
    protected $rpcHandlers = [];

    /**
     * @param RpcInterface $rpcHandler
     */
    public function addRpc(RpcInterface $rpcHandler)
    {
        $this->rpcHandlers[$rpcHandler->getName()] = $rpcHandler;
    }

    /**
     * @param string $name
     *
     * @return RpcInterface
     *
     * @throws \InvalidArgumentException
     */
    public function getRpc($name)
    {
        if (!$this->hasRpc($name)) {
            throw new \InvalidArgumentException(sprintf('A RPC handler named "%s" has not been registered.', $name));
        }

        return $this->rpcHandlers[$name];
    }

    public function hasRpc(string $name): bool
    {
        return isset($this->rpcHandlers[$name]);
    }
}
