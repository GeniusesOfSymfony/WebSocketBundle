# Upgrade from 2.x to 3.0

## Changes

- The minimum supported PHP version is now 7.4
- The minimum supported Symfony version is now 4.4
- Renamed `Gos\Bundle\WebSocketBundle\DataCollector\PusherDecorator` to `Gos\Bundle\WebSocketBundle\Pusher\DataCollectingPusherDecorator`
- Made event classes final
- The methods of `Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface` now return an array containing instances of `Gos\Bundle\WebSocketBundle\Client\ClientConnection`, accessing the array properties is supported however deprecated and will be removed in 4.0.

## Removals

- Removed deprecated service IDs
- Removed deprecated `Gos\Bundle\WebSocketBundle\Event\Events` class, use `Gos\Bundle\WebSocketBundle\GosWebSocketEvents` instead
- Removed deprecated `Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface::findByUsername()` method, use `Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface::findAllByUsername()` instead
- Removed deprecated `Gos\Bundle\WebSocketBundle\Client\Driver\PredisDriver` class, use another supported storage driver
- Removed deprecated `Gos\Bundle\WebSocketBundle\RPC\RpcResponse` class, return responses from RPC handlers as arrays or implement a custom dispatcher with support for DTOs
