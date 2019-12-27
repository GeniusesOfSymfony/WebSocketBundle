# Upgrade from 2.x to 3.0

## Changes

- The minimum supported PHP version is now 7.4
- The minimum supported Symfony version is now 4.4
- Renamed `Gos\Bundle\WebSocketBundle\DataCollector\PusherDecorator` to `Gos\Bundle\WebSocketBundle\Pusher\DataCollectingPusherDecorator`
- Made event classes final

## Removals

- Removed deprecated service IDs
- Removed deprecated `Gos\Bundle\WebSocketBundle\Event\Events` class, use `Gos\Bundle\WebSocketBundle\GosWebSocketEvents` instead
- Removed deprecated `Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface::findByUsername()` method, use `Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface::findAllByUsername()` instead
- Removed deprecated `Gos\Bundle\WebSocketBundle\Client\Driver\PredisDriver` class, use another supported storage driver
