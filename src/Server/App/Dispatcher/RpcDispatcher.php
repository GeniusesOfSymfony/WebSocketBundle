<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\RpcRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampConnection;
use function Symfony\Component\String\u;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class RpcDispatcher implements RpcDispatcherInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private RpcRegistry $rpcRegistry;

    public function __construct(RpcRegistry $rpcRegistry)
    {
        $this->rpcRegistry = $rpcRegistry;
    }

    public function dispatch(ConnectionInterface $conn, string $id, Topic $topic, WampRequest $request, array $params): void
    {
        if (!($conn instanceof WampConnection)) {
            throw new \InvalidArgumentException(sprintf('Argument 1 of %1$s() must be an instance of %2$s, a %3$s instance was given.', __METHOD__, WampConnection::class, \get_class($conn)));
        }

        $callback = $request->getRoute()->getCallback();

        if (!\is_string($callback)) {
            throw new \InvalidArgumentException(sprintf('The callback for route "%s" must be a string, a callable was given.', $request->getRouteName()));
        }

        if (!$this->rpcRegistry->hasRpc($callback)) {
            if (null !== $this->logger) {
                $this->logger->error(sprintf('Could not find RPC handler in registry for callback "%s".', $callback));
            }

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

        $method = $this->toCamelCase($request->getAttributes()->get('method', ''));

        if (!method_exists($procedure, $method)) {
            if (null !== $this->logger) {
                $this->logger->error(
                    sprintf('Method "%s" not found in %s.', $method, \get_class($procedure)),
                    [
                        'called_method' => $request->getAttributes()->get('method'),
                    ]
                );
            }

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
            $result = $procedure->$method($conn, $request, $params);
        } catch (\Throwable $e) {
            if (null !== $this->logger) {
                $this->logger->error(
                    'Websocket error processing RPC function.',
                    [
                        'exception' => $e,
                        'rpc' => $topic,
                    ]
                );
            }

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

        if (null === $result || false === $result) {
            if (null !== $this->logger) {
                $this->logger->error(sprintf('Invalid return from RPC handler %s::%s(), the return cannot be null or boolean false.', \get_class($procedure), $method));
            }

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

        if (!\is_array($result)) {
            $result = [$result];
        }

        $conn->callResult($id, $result);
    }

    private function toCamelCase(string $str): string
    {
        return u($str)->camel()->toString();
    }
}
