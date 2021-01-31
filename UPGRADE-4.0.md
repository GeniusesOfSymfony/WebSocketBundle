# Upgrade from 3.x to 4.0

## Changes

- Minimum required Ratchet version is now 0.5
- Added support for `gos/pubsub-router-bundle` 3.0
- Added return typehint to `Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface::onPush()`
- Added return typehint to `Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface::launch()`
- Added return typehints to `Gos\Bundle\WebSocketBundle\Topic\TopicInterface`
- Renamed the `gos_web_socket.event_listener.client` service to `gos_web_socket.event_subscriber.client`
- Renamed the `Gos\Bundle\WebSocketBundle\EventListener\ClientEventListener` class to `Gos\Bundle\WebSocketBundle\EventListener\WebsocketClientEventSubscriber`
- Renamed the `gos_web_socket.event_listener.start_server` service to `gos_web_socket.event_listener.bind_signals_to_websocket_server`
- Renamed the `Gos\Bundle\WebSocketBundle\EventListener\StartServerListener` class to `Gos\Bundle\WebSocketBundle\EventListener\BindSignalsToWebsocketServerEventListener`
- Made private members of the `GosSocket` JavaScript class private using the class fields and private method proposals
- Reworked the `websocket.js` file so that the provided public resource only supports modern browsers, the `assets/js/websocket.js` file can be included in your project's build tools and transpiled to support the browsers your project needs if the provided file does not work
- Removed the `WS` class and `Socket` global variable in the `websocket.js` file, use the new static `GosSocket.connect()` method as a replacement for `WS.connect()` and store the singleton within your application if necessary
- Added `Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface::removeAllClients()`
- Added `Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface::clear()`
- `Gos\Bundle\WebSocketBundle\EventListener\BindSignalsToWebsocketServerEventListener` will now clear the client storage when a shutdown signal is received
- The compiler passes and event listeners are now internal, they are not intended for direct use by bundle users and B/C will no longer be guaranteed on them
- Made `Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcher::dispatch()` a private method
- Made the class constants from `Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcher` private

## Deprecations

- Deprecated support for event names, register event listeners using the event class instead

## Removals

- Removed the pusher and server push handler integrations, Symfony's Messenger component is the suggested replacement
- Removed unused `gos_web_socket.client.storage.prefix` configuration node and container parameter
- Removed `ArrayAccess` support from `Gos\Bundle\WebSocketBundle\Client\ClientConnection`
- Removed support for `Doctrine\DBAL\Driver\PingableConnection` implementations in `Gos\Bundle\WebSocketBundle\Periodic\DoctrinePeriodicPing`, only `Doctrine\DBAL\Connection` instances are supported
- Removed `Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent::setException()`, a `Throwable` instance is now a required constructor argument
- Removed `Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent::getException()`, use `Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent::getThrowable()` instead
- Removed support for the `gos/websocket-client` package, use `ratchet/pawl` instead
- Removed `Gos\Bundle\WebSocketBundle\Client\Driver\DoctrineCacheDriverDecorator`, if using the `doctrine/cache` package a `Gos\Bundle\WebSocketBundle\Client\Driver\SymfonyCacheDriverDecorator` using a `Symfony\Component\Cache\DoctrineProvider` instance can be used
- Removed `Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface::dispatch()`, the method is no longer a required on interface implementations
