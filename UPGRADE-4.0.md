# Upgrade from 3.x to 4.0

## Changes

- Added return typehint to `Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface::onPush()`
- Added return typehint to `Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface::launch()`
- Added return typehints to `Gos\Bundle\WebSocketBundle\Topic\TopicInterface`
- The `gos/websocket-client` is no longer always installed with this bundle, explicitly require it in your application if you are using the websocket client
- Renamed the `gos_web_socket.event_listener.client` service to `gos_web_socket.event_subscriber.client`
- Renamed the `Gos\Bundle\WebSocketBundle\EventListener\ClientEventListener` class to `Gos\Bundle\WebSocketBundle\EventListener\WebsocketClientEventSubscriber`
- Renamed the `gos_web_socket.event_listener.start_server` service to `gos_web_socket.event_listener.bind_sigint_signal_to_websocket_server`
- Renamed the `Gos\Bundle\WebSocketBundle\EventListener\StartServerListener` class to `Gos\Bundle\WebSocketBundle\EventListener\BindSigintSignalToWebsocketServerEventListener`

## Deprecations

- Deprecated support for event names, register event listeners using the event class instead

## Removals

- Removed the pusher and server push handler integrations, Symfony's Messenger component is the suggested replacement
- Removed unused `gos_web_socket.client.storage.prefix` configuration node and container parameter
- Removed `ArrayAccess` support from `Gos\Bundle\WebSocketBundle\Client\ClientConnection`
- Removed support for `Doctrine\DBAL\Driver\PingableConnection` implementations in `Gos\Bundle\WebSocketBundle\Periodic\DoctrinePeriodicPing`, only `Doctrine\DBAL\Connection` instances are supported
