#Periodic Function Services

With realtime applications, sometimes you need code to be executed regardless of events, e.g. a matchmaking engine.

With Gos WebSocket these can easily be added and will run within the [React Server](http://reactphp.org/) event loop.

##Step 1: Create the Periodic Service Class

Every periodic service must implement the PeriodicInterface.

```php
<?php

namespace Acme\HomeBundle\Periodic;

use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;

class AcmePeriodic implements PeriodicInterface
{
    /**
     * This function is executed every 5 seconds.
     *
     * For more advanced functionality, try injecting a Topic Service to perform actions on your connections every x seconds.
     */
    public function tick()
    {
        echo "Executed once every 5 seconds" . PHP_EOL;
    }
    
    public function getTimeout()
    {
        return 5000;
    }
}

```

##Step 2: Register your service with Symfony

If you are using YML, edit "YourBundle/Resources/config/services/services.yml"
For other formats, please check the [Symfony2 Documents](http://symfony.com/doc/master/book/service_container.html)

```yaml
services:
    acme_hello.periodic_sample_service:
        class: Acme\HelloBundle\Periodic\AcmePeriodic
```

From now you can directly tag your service to register your service into GosWebSocket

```yaml
services:
    acme_hello.periodic_sample_service:
        class: Acme\HelloBundle\Periodic\AcmePeriodic
        tags:
            - { name: gos_web_socket.periodic }
```

**or** register via "app/config/config.yml"

```yaml
gos_web_socket:
    periodic:
        - @acme_hello.periodic_sample_service
```

Try pairing up a Periodic function with a [Custom Topic handler](TopicSetup.md) to perform actions on a set of clients connected to a certain topic.
