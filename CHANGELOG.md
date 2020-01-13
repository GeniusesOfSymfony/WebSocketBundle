# Changelog

## 2.1.1 (2020-??-??)

- Remove pusher services from the container when pushers are not enabled

## 2.1.0 (2020-01-07)

- Deprecated the `Gos\Bundle\WebSocketBundle\RPC\RpcResponse` class, return responses from RPC handlers as arrays or implement a custom dispatcher with support for DTOs
- Widened the types allowed in the `Gos\Bundle\WebSocketBundle\Server\WampServer constructor`, now any `Ratchet\Wamp\WampServerInterface` implementation can be accepted
