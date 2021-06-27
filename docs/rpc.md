# Creating RPC Handlers

Every remote procedure call (RPC) in GosWebSocketBundle has its own "network namespace" in order to dispatch requests to the correct command.

In your Symfony application, RPC handlers are defined as services in your dependency injection container.

## Overview

- Create the service class
- Register your service with Symfony
- Add a route for your service

## Step 1: Create the service class

Your service is a PHP class which must implement `Gos\Bundle\WebSocketBundle\RPC\RpcInterface`.

For each method you want to expose as an RPC handler, the method must use the following signature:

```php
<?php

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\ConnectionInterface;

public function myHandler(ConnectionInterface $connection, WampRequest $request, $params);
```

- The `$connection` is the websocket connection for the user executing the RPC call
- The `$request` is the request data for the RPC call (similar to the `Symfony\Component\HttpFoundation\Request` object in controllers)
- The `$params` is an array of parameters for the RPC call (similar to the `$_POST` data of an HTTP request)

```php
<?php

namespace App\Websocket\Rpc;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Ratchet\ConnectionInterface;

final class AcmeRpc implements RpcInterface
{
    /**
     * Adds the params together.
     */
    public function sum(ConnectionInterface $connection, WampRequest $request, $params): array
    {
        return ['result' => array_sum($params)];
    }

    /**
     * Name of the RPC handler.
     */
    public function getName(): string
    {
        return 'acme.rpc';
    }
}
```

To return a result from the procedure, simply return anything other than false or null. All return values must be something that can be JSON encoded.

If you return false or null, it will return an error to the client, informing them the procedure call did not work correctly.

## Step 2: Register your service with Symfony

RPC handlers must be tagged with the `gos_web_socket.rpc` tag to be correctly registered. When autowiring is enabled, your service will be automatically tagged.

```yaml
# config/services.yaml
services:
    App\Websocket\Rpc\AcmeRpc:
        tags:
            - { name: gos_web_socket.rpc }
```

## Step 3: Add a route for your service

Now that you have created your RPC service, you must now add a route to the service so the bundle can send RPC calls to your class. `sample/sum` will refer to the service you've created.

Using the configuration from the installation steps, you can add the following file to your Symfony application at `config/pubsub/websocket/routing.yaml`

```yaml
acme_rpc:
    # A unique URI pattern to identify this RPC handler 
    pattern: sample/{method}

    # Must match the `getName` method of your RPC class
    callback: 'acme.rpc'

    # A list of requirements to match the URI pattern
    requirements:
        method: "[a-z_]+"
```

From here, each call that matches this pattern will handled by the `AcmeRpc` class.

Similar to Symfony's Routing component, you can define multiple routes in a single file.

```yaml
acme_abc_rpc:
    pattern: sample/{method}
    callback: 'acme.method_abc.rpc'
    requirements:
        method: "method_a|method_b|method_c"
            
acme_de_rpc:
    pattern: sample/{method}
    callback: 'acme.method_de.rpc'
    requirements:
        method: "method_d|method_e"
```

### Step 4: Call an RPC function with the JavaScript client

You can now call your RPC function using the JavaScript client.

e.g.

```javascript
session.call('sample/sum', {'value1': 2, 'value2': 5}).then(
    function (result) {
        console.log('Sum of values: ' + result);
    },
    function (error, desc) {
        console.log('RPC Error', error, desc);
    }
);
```

For more information on the JavaScript Client the bundle, please see [Client Side Setup](javascript-client.md)
