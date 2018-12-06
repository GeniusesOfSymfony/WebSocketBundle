# Upgrade from 1.x to 2.0

## Additions

- Added autoconfiguration support
    - `Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface` (`gos_web_socket.periodic` tag)
    - `Gos\Bundle\WebSocketBundle\RPC\RpcInterface` (`gos_web_socket.rpc` tag)
    - `Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface` (`gos_web_socket.server` tag)
    - `Gos\Bundle\WebSocketBundle\Topic\TopicInterface` (`gos_web_socket.topic` tag)

## Changes

- The `Gos\Bundle\WebSocketBundle\Event\ServerEvent` now requires a third argument, `$profile`, indicating if profiling is enabled (i.e. the `--profile` option from the `gos:websocket:server` command)
- `Gos\Bundle\WebSocketBundle\Pusher\PusherInterface` now includes a `setName()` method
- `Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry::addPusher()` and `Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry::addPushHandler()` no longer have a separate `$name` argument, the name from the injected object is used instead
- All bundle services have been explicitly marked public or private
- Registering periodic timers and push handlers in the default websocket server (`Gos\Bundle\WebSocketBundle\Server\Type\WebSocketServer`) has been extracted to event listeners subscribed to the `gos_web_socket.server_launched` event

## Removals

- The minimum supported Symfony version is now 3.4
- Removed support for the [AsseticBundle](https://github.com/symfony/assetic-bundle) as it itself is deprecated
    - The `client.html.twig` file, which loaded assets with Assetic was removed as a result
    - The `ws_client()` Twig function, which rendered the above file, was also removed
    - The `gos_web_socket.assetic` configuration node should be removed from your application
- Removed deprecated classes/traits/interfaces
    - `Gos\Bundle\WebSocketBundle\Client\DoctrineCacheDriverDecorator` was removed, use `Gos\Bundle\WebSocketBundle\Client\Driver\DoctrineCacheDriverDecorator` instead
    - `Gos\Bundle\WebSocketBundle\Client\WebSocketUserTrait` was removed, inject the `@gos_web_socket.websocket.client_manipulator` service instead
- Removed the following configuration nodes, these services should be tagged instead
    - `gos_web_socket.periodic`, tag your services with the `gos_web_socket.periodic` tag
    - `gos_web_socket.rpc`, tag your services with the `gos_web_socket.rpc` tag
    - `gos_web_socket.servers`, tag your services with the `gos_web_socket.server` tag
    - `gos_web_socket.topics`, tag your services with the `gos_web_socket.topic` tag
- The `gos:server` command was removed, use the `gos:websocket:server` command instead
