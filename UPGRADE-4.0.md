# Upgrade from 3.x to 4.0

## Changes

- Added return typehint to `Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface::onPush()`
- Added return typehint to `Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface::launch()`
- Added return typehints to `Gos\Bundle\WebSocketBundle\Topic\TopicInterface`
- The `gos/websocket-client` is no longer always installed with this bundle, explicitly require it in your application if you are using the websocket client

## Removals

- Removed the pusher and server push handler integrations, Symfony's Messenger component is the suggested replacement
- Removed unused `gos_web_socket.client.storage.prefix` configuration node and container parameter
- Removed `ArrayAccess` support from `Gos\Bundle\WebSocketBundle\Client\ClientConnection`
