# Creating Periodic Functions

With real-time applications, sometimes you need code to be executed regardless of whether there are clients connected to the server or a specific Topic (channel). With the GosWebSocketBundle, these can easily be added and will run within the [React Server](https://reactphp.org/) event loop.

## Overview

- Create the service class
- Register your service with Symfony

## Step 1: Create the service class

Your service is a PHP class which must implement `Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface`.

```php
<?php

namespace App\Websocket\Periodic;

use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;

class AcmePeriodic implements PeriodicInterface
{
    /**
     * This function is executed every 5 seconds, as specified by the `getInterval()` method.
     */
    public function tick(): void
    {
        echo "It has been 5 seconds since this was last run" . PHP_EOL;
    }

    /**
     * Defines the interval for a periodic service, the service will be executed at the interval specified by this method.
     */
    public function getInterval(): int
    {
        return 5;
    }

    /**
     * Deprecated method for defining the interval for a periodic service, this method must be implemented until 4.0 is released
     *
     * @deprecated to be removed when updating to GosWebSocketBundle 4.0
     */
    public function getTimeout(): int
    {
        return $this->getInterval();
    }
}
```

## Step 2: Register your service with Symfony

Periodic services must be tagged with the `gos_web_socket.periodic` tag to be correctly registered. Note that when autowiring is enabled, your service will be automatically tagged.

```yaml
# config/services.yaml
services:
    App\Websocket\Periodic\AcmePeriodic:
        tags:
            - { name: gos_web_socket.periodic }
```
