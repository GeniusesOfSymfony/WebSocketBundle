<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\RPC\RpcResponse;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\RpcRegistry;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class RpcDispatcher implements RpcDispatcherInterface
{
    /**
     * @var RpcRegistry
     */
    protected $rpcRegistry;

    public function __construct(RpcRegistry $rpcRegistry)
    {
        $this->rpcRegistry = $rpcRegistry;
    }

    public function dispatch(ConnectionInterface $conn, string $id, Topic $topic, WampRequest $request, array $params): void
    {
        $callback = $request->getRoute()->getCallback();

        if (!$this->rpcRegistry->hasRpc($callback)) {
            $conn->callError(
                $id,
                $topic,
                sprintf('A RPC handler for the "%s" route has not been registered.', $request->getRouteName()),
                [
                    'code' => 404,
                    'rpc' => $topic,
                    'params' => $params,
                    'request' => $request,
                ]
            );

            return;
        }

        $procedure = $this->rpcRegistry->getRpc($callback);

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
