# Remote Procedure Call Setup

Every remote procedure call (RPC) in GosWebSocketBundle has its own "network namespace" in order to dispatch requests to the correct command.

In your Symfony application, RPCs are setup as services. This allows you full control of what to do with the class, whether its a mailer or an entity manager.

If you are new to services, please see [Symfony: Service Container](http://symfony.com/doc/master/book/service_container.html)

## Overview

* Create the service class
* Register your service with Symfony
* Register your service with GosPubSubRouterBundle

## Step 1: Create the Service Class

Your service is a PHP class which must implement `Gos\Bundle\WebSocketBundle\RPC\RpcInterface`.

For each method you want to expose as a RPC handler, the method must use the following signature:

```php
<?php

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\ConnectionInterface;

public function myHandler(ConnectionInterface $connection, WampRequest $request, $params);
```

* The `$connection` is the websocket connection for the user executing the RPC call
* The `$request` is the request data for the RPC call (similar to the `Symfony\Component\HttpFoundation\Request` object in controllers)
* The `$params` is an array of parameters for the RPC call (similar to the `$_POST` data of a HTTP request)

```php
<?php

namespace App\Websocket\Rpc;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Ratchet\ConnectionInterface;

final class AcmeRpc implements RpcInterface
{
    /**
     * Adds the params together
     *
     * Note: $connection isn't used here, but contains the connection of the user making this request.
     *
     * @param ConnectionInterface $connection
     * @param WampRequest $request
     * @param array $params
     *
     * @return array
     */
    public function sum(ConnectionInterface $connection, WampRequest $request, $params)
    {
        return ['result' => array_sum($params)];
    }

    /**
     * Name of the RPC handler, used by the PubSub router.
     *
     * @return string
     */
    public function getName()
    {
        return 'acme.rpc';
    }
}
```

To return a result from the procedure, simply return anything other than false or null. If you pass an array, its automatically converted to a JSON Object.

If you return false or null, it will return an error to the client, informing them the procedure call did not work correctly.

## Step 2: Register your service with Symfony

For an application based on the Symfony Standard structure, you can register services in either your `app/config/services.yml` file or your bundle's `Resources/config/services.yml` file. For an application based on Symfony Flex, use the `config/services.yaml` file.

RPC handlers must be tagged with the `gos_web_socket.rpc` tag to be correctly registered.

```yaml
services:
    app.websocket.rpc.acme:
        class: App\Websocket\Rpc\AcmeRpc
        tags:
            - { name: gos_web_socket.rpc }
```

For other formats, please review the [Symfony Documentation](http://symfony.com/doc/master/book/service_container.html).

### Alternative Service Registration (Deprecated)

Alternatively, you can list your RPC services in the bundle's configuration file. Note, this method is deprecated and removed in GosWebSocketBundle 2.0.

```yaml
gos_web_socket:
    rpc:
        - '@app.websocket.rpc.acme'
```

By using network namespaces, this allows you to logically divide and group your application's handlers.

## Step 3: Register your service with GosPubSubRouterBundle

Now that you have created your RPC service, you must now link the path with your service. `sample/sum` will refer to the service you've created.

If not already created, you should create a routing file for the GosPubSubRouterBundle configuration. For Symfony Standard, you should use either `app/config/pubsub/routing.yml` or your bundle's `Resources/config/pubsub/routing.yml`. For Symfony Flex, you should use `config/pubsub/routing.yaml`.

```yaml
acme_rpc:
    channel: sample/{method}
    handler:
        callback: 'acme.rpc' #related to the getName() or your RPC service
    requirements:
        method:
            pattern: "[a-z_]+"
```

Next, you will need to include the new resource in the bundle's configuration to ensure the PubSub router is set up correctly.

```yaml
gos_web_socket:
    server:
        port: 8080
        host: 127.0.0.1
        router:
            resources:
                - '%kernel.project_dir%/config/pubsub/routing.yaml'
```

From here, each call that matches with this pattern will handled by the `AcmeRpc` class.

Similar to Symfony's Routing component, you can define multiple routes in a single file.

```yaml
acme_abc_rpc:
    channel: sample/{method}
    handler:
        callback: 'acme.method_abc.rpc'
    requirements:
        method:
            pattern: "method_a|method_b|method_c"
            
acme_de_rpc:
    channel: sample/{method}
    handler:
        callback: 'acme.method_de.rpc'
    requirements:
        method:
            pattern: "method_d|method_e"
```

### Step 4: Call a RPC function with the JavaScript client

You can now call your RPC function using the JavaScript client.

e.g.

```javascript
session.call("sample/sum", {"term1": 2, "term2": 5}).then(
    function (result) {
        console.log("RPC Valid!", result);
    },
    function (error, desc) {
        console.log("RPC Error", error, desc);
    }
);
```

For more information on the JavaScript Client the bundle, please see [Client Side Setup](ClientSetup.md)
