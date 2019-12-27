<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\RPC;

interface RpcInterface
{
    public function getName(): string;
}
