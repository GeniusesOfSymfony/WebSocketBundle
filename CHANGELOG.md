# Changelog

## 2.3.1 (2020-03-13)

- Remove the aliases for pusher services from the container when removing the real services (Fixes [#406](https://github.com/GeniusesOfSymfony/WebSocketBundle/issues/406))

## 2.3.0 (2020-02-25)

- Add support for the new router configuration options available in GosPubSubRouterBundle 2.2

## 2.2.0 (2020-01-29)

- Remove pusher services from the container when pushers are not enabled
- Add new subclasses of `Gos\Bundle\WebSocketBundle\Event\ClientEvent` for the `GosWebSocketEvents::CLIENT_CONNECTED` and `GosWebSocketEvents::CLIENT_DISCONNECTED` events
- Deprecated `Gos\Bundle\WebSocketBundle\Event\ClientEvent::getType()`, the `$type` argument of the class' constructor, and the type constants within the class; check the event type based on the subclass instead
- Add new subclasses of `Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent` for the `GosWebSocketEvents::PUSHER_SUCCESS` and `GosWebSocketEvents::PUSHER_FAIL` events
- Deprecated `Gos\Bundle\WebSocketBundle\Event\ServerEvent`, use the `Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent` class instead

## 2.1.0 (2020-01-07)

- Deprecated the `Gos\Bundle\WebSocketBundle\RPC\RpcResponse` class, return responses from RPC handlers as arrays or implement a custom dispatcher with support for DTOs
- Widened the types allowed in the `Gos\Bundle\WebSocketBundle\Server\WampServer constructor`, now any `Ratchet\Wamp\WampServerInterface` implementation can be accepted
