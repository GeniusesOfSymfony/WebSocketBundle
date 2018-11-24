<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\RPC\RpcResponse;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\RpcRegistry;
use Ratchet\ConnectionInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class RpcDispatcher implements RpcDispatcherInterface
{
    /**
     * @var RpcRegistry
     */
    protected $rpcRegistry;

    /**
     * @param RpcRegistry $rpcRegistry
     */
    public function __construct(RpcRegistry $rpcRegistry)
    {
        $this->rpcRegistry = $rpcRegistry;
    }

    /**
     * @param ConnectionInterface $conn
     * @param string              $id
     * @param string              $topic
     * @param WampRequest         $request
     * @param array               $params
     */
    public function dispatch(ConnectionInterface $conn, $id, $topic, WampRequest $request, array $params)
    {
        $callback = $request->getRoute()->getCallback();

        try {
            $procedure = $this->rpcRegistry->getRpc($callback);
        } catch (\Exception $e) {
            $conn->callError(
                $id,
                $topic,
                $e->getMessage(),
                [
                    'code' => $e->getCode(),
                    'rpc' => $topic,
                    'params' => $params,
                    'request' => $request,
                ]
            );

            return;
        }

        $method = $this->toCamelCase($request->getAttributes()->get('method'));

        if (!method_exists($procedure, $method)) {
            $conn->callError(
                $id,
                $topic,
                'Could not execute RPC callback, method not found',
                [
                    'code' => 404,
                    'rpc' => $topic,
                    'params' => $params,
                    'request' => $request,
                ]
            );

            return;
        }

        try {
            $result = call_user_func([$procedure, $method], $conn, $request, $params);
        } catch (\Exception $e) {
            $conn->callError(
                $id,
                $topic,
                $e->getMessage(),
                [
                    'code' => $e->getCode(),
                    'rpc' => $topic,
                    'params' => $params,
                    'request' => $request,
                ]
            );

            return;
        }

        if ($result === null || $result === false) {
            $conn->callError(
                $id,
                $topic,
                'RPC Error',
                [
                    'code' => 500,
                    'rpc' => $topic,
                    'params' => $params,
                    'request' => $request,
                ]
            );

            return;
        }

        if ($result instanceof RpcResponse) {
            $result = $result->getData();
        } elseif (!is_array($result)) {
            $result = [$result];
        }

        $conn->callResult($id, $result);
    }

    private function toCamelCase(?string $str): string
    {
        return preg_replace_callback(
            '/_([a-z])/',
            function ($c): string {
                return strtoupper($c[1]);
            },
            $str
        );
    }
}
