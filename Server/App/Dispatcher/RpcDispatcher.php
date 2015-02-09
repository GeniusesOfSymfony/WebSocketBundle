<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Dispatcher;

use Gos\Bundle\WebSocketBundle\RPC\RpcResponse;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\RpcRegistry;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
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
     * @param array               $params
     */
    public function dispatch(ConnectionInterface $conn, $id, TopicInterface $topic, array $params)
    {
        $parts = explode("/", $topic->getId());

        if (count($parts) < 2) {
            $conn->callError($id, $topic, "Incorrectly formatted Topic name",  ["topic_name" => $topic->getId()]);

            return;
        }

        try {
            $handler = $this->rpcRegistry->getRpc($parts[0]);
        } catch (\Exception $e) {
            $conn->callError($id, $topic, $e->getMessage(), ["topic_name" => $topic->getId()]);

            return;
        }

        $method = $this->toCamelCase($parts[1]);
        $result = null;

        try {
            $result = call_user_func([$handler, $method], $conn, $params);
        } catch (\Exception $e) {
            $conn->callError($id, $topic, $e->getMessage(),  ["code" => $e->getCode(), "rpc" => $topic->getId(), "params" => $params]);

            return;
        }

        if ($result === null) {
            $result = false;
        }

        if ($result) {
            if ($result instanceof RpcResponse) {
                $result = $result->getData();
            } elseif (!is_array($result)) {
                $result = [$result];
            }

            $conn->callResult($id, $result);

            return;
        } elseif ($result === false) {
            $conn->callError($id, $topic, "RPC Failed",  ["rpc" => $topic->getId(), "params" => $params]);
        }

        $conn->callError($id, $topic, "Unable to find that command",  ["rpc" => $topic->getId(), "params" => $params]);

        return;
    }

    /**
     * source: http://www.paulferrett.com/2009/php-camel-case-functions/
     *
     * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
     * @param  string $str                   String in underscore format
     * @param  bool   $capitalise_first_char If true, capitalise the first char in $str
     * @return string $str translated into camel caps
     */
    protected function toCamelCase($str, $capitalise_first_char = false)
    {
        if ($capitalise_first_char) {
            $str[0] = strtoupper($str[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');

        return preg_replace_callback('/_([a-z])/', $func, $str);
    }
}
