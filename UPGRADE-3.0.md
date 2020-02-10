# Upgrade from 2.x to 3.0

## Changes

- The minimum supported PHP version is now 7.4
- The minimum supported Symfony version is now 4.4
- Renamed `Gos\Bundle\WebSocketBundle\DataCollector\PusherDecorator` to `Gos\Bundle\WebSocketBundle\Pusher\DataCollectingPusherDecorator`
- Renamed `Gos\Bundle\WebSocketBundle\EventListener\KernelTerminateListener` (service ID `gos_web_socket.event_listener.kernel_terminate`) to `Gos\Bundle\WebSocketBundle\EventListener\ClosePusherConnectionsListener` (service ID `gos_web_socket.event_listener.close_pusher_connections`)
- Made event classes final
- The methods of `Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface` now return an array containing instances of `Gos\Bundle\WebSocketBundle\Client\ClientConnection`, accessing the array properties is supported however deprecated and will be removed in 4.0.
- The `Gos\Bundle\WebSocketBundle\Event\ClientEvent` and `Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent` classes are now abstract

## Removals

- Removed deprecated service IDs
- Removed deprecated `Gos\Bundle\WebSocketBundle\Event\Events` class, use `Gos\Bundle\WebSocketBundle\GosWebSocketEvents` instead
- Removed deprecated `Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface::findByUsername()` method, use `Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface::findAllByUsername()` instead
- Removed deprecated `Gos\Bundle\WebSocketBundle\Client\Driver\PredisDriver` class, use another supported storage driver
- Removed deprecated `Gos\Bundle\WebSocketBundle\RPC\RpcResponse` class, return responses from RPC handlers as arrays or implement a custom dispatcher with support for DTOs
- Removed `Gos\Bundle\WebSocketBundle\Event\ClientEvent::getType()`, the `$type` argument from the class' constructor, and the type constants in the class; each event type now has a distinct subclass
- Removed deprecated `Gos\Bundle\WebSocketBundle\Event\ServerEvent` class, use `Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent` instead
