# Periodic Services

With real-time applications, sometimes you need code to be executed regardless of whether there are clients connected to the server or a specific Topic (channel). With the GosWebSocketBundle, these can easily be added and will run within the [React Server](http://reactphp.org/) event loop.

## Step 1: Create the Periodic Service

Your service is a PHP class which must implement `Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface`.

```php
<?php

namespace App\Websocket\Periodic;

use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;

class AcmePeriodic implements PeriodicInterface
{
    /**
     * This function is executed every 5 seconds, as specified by the `getTimeout()` method.
     */
    public function tick()
    {
        echo "It has been 5 seconds since this was last run" . PHP_EOL;
    }

    /**
     * Defines the timeout for a periodic service, the service will be executed at the interval specified by this method.
     *
     * @return int Time between "ticks" of this service, in seconds
     */
    public function getTimeout()
    {
        return 5;
    }
}
```

## Step 2: Register your service with Symfony

For an application based on the Symfony Standard structure, you can register services in either your `app/config/services.yml` file or your bundle's `Resources/config/services.yml` file. For an application based on Symfony Flex, use the `config/services.yaml` file.

Periodic services must be tagged with the `gos_web_socket.periodic` tag to be correctly registered.

```yaml
services:
    app.websocket.periodic.acme:
        class: App\Websocket\Periodic\AcmePeriodic
        tags:
            - { name: gos_web_socket.periodic }
```

For other formats, please review the [Symfony Documentation](http://symfony.com/doc/master/book/service_container.html).

### Alternative Service Registration (Deprecated)

Alternatively, you can list your Periodic services in the bundle's configuration file. Note, this method is deprecated and removed in GosWebSocketBundle 2.0.

```yaml
gos_web_socket:
    periodic:
        - '@app.websocket.periodic.acme'
```

Try pairing up a Periodic function with a [Custom Topic handler](TopicSetup.md) to perform actions on a set of clients connected to a certain topic.
