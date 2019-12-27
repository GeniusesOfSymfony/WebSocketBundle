# WebSocket Bundle Events

The GosWebSocketBundle provides events which can be used to hook into actions performed by the bundle.

## Available Events

- `gos_web_socket.server_launched` is dispatched when the websocket server is launched, listeners receive a `Gos\Bundle\WebSocketBundle\Event\ServerEvent` object
- `gos_web_socket.client_connected` is dispatched when a client connects to the websocket server, listeners receive a `Gos\Bundle\WebSocketBundle\Event\ClientEvent` object
- `gos_web_socket.client_disconnected` is dispatched when a client disconnects from the websocket server, listeners receive a `Gos\Bundle\WebSocketBundle\Event\ClientEvent` object
- `gos_web_socket.client_error` is dispatched when a client connection has an error, listeners receive a `Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent` object
- `gos_web_socket.client_rejected` is dispatched when a client connection is rejected by the websocket server, listeners receive a `Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent` object
- `gos_web_socket.push_fail` is dispatched when a server push fails, listeners receive a `Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent` object
- `gos_web_socket.push_success` is dispatched when a server push succeeds, listeners receive a `Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent` object

## Creating an event listener

To create an event listener, please follow the [Symfony documentation](https://symfony.com/doc/current/event_dispatcher.html).
