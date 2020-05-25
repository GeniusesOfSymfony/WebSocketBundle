# Upgrade from 3.x to 4.0

## Removals

- Removed the pusher and server push handler integrations, Symfony's Messenger component is the suggested replacement
- Removed unused `gos_web_socket.client.storage.prefix` configuration node and container parameter
- Removed `ArrayAccess` support from `Gos\Bundle\WebSocketBundle\Client\ClientConnection`
