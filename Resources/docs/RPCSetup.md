#Remote Procedure Call Setup

Every remote procedure call (RPC) in Gos WebSocket has its own "network namespace" in order to dispatch requests to the correct command.

In Symfony RPCs are setup as services. This allows you full control of what to do with the class, whether its a mailer or an entity manager.

If you are new to services, please see [Symfony2: Service Container](http://symfony.com/doc/master/book/service_container.html)

##Overview
* Create the service class
* Register you service with Symfony
* Register your service with PubSubRouter

##Step 1: Create the Service Class

```php
<?php

namespace Acme\HelloBundle\RPC;

use Ratchet\ConnectionInterface;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;

class AcmeService implements RpcInterface
{
    /**
     * Adds the params together
     *
     * Note: $conn isnt used here, but contains the connection of the person making this request.
     *
     * @param ConnectionInterface $connection
     * @param WampRequest $request
     * @param array $params
     * @return int
     */
    public function addFunc(ConnectionInterface $connection, WampRequest $request, $params)
    {
        return array("result" => array_sum($params));
    }
    
    /**
     * Name of RPC, use for pubsub router (see step3)
     * 
     * @return string
     */
    public function getName()
    {
        return 'acme.rpc';
    }
}
```

_Note: the function called in the client side is "add_func". Gos WebSocket automatically converts this to CamelCase for the server._

To return a result from the procedure, simply return anything other than false or null. If you pass an array, its automatically converted to a JSON Object.

If you return false or null, it will return an error to the client, informing them the procedure call did not work correctly.

##Step 2: Register your service with Symfony

If you are using YML, edit "YourBundle/Resources/config/services.yml", add:

```yaml
services:
    acme_hello.rpc_sample_service:
        class: Acme\HelloBundle\RPC\AcmeService
```
From now you can directly tag your service to register your service into GosWebSocket

```yaml
services:
    acme_hello.rpc_sample_service:
        class: Acme\HelloBundle\RPC\AcmeService
        tags:
            - { name: gos_web_socket.rpc }
```
For other formats, please check the [Symfony2 Documents](http://symfony.com/doc/master/book/service_container.html)

**or** register via "app/config/config.yml"

```yaml
gos_web_socket:
    rpc:
        - @acme_hello.rpc_sample_service
```

The domain will match the network namespace for sending messages to this service.

e.g.

```javascript
    //using "then" promises.
    session.call("sample/add_func", {"term1": 2, "term2": 5}).then(  
        function(result)
        {
            console.log("RPC Valid!", result);
        },
        function(error, desc)
        {
            console.log("RPC Error", error, desc);
        }
    );
```


The idea of having these network namespaces is to group relevant code into separate files.

##Step 3: Register your service with PubSubRouter

Now you have create your RPC service and implements your RPC call in the client, you must now link the path with your service.  `sample/add_func` will refer to AcmeService

if he not already exists, create `AcmeBundle/Resources/config/pubsub/routing.yml` and register it in the websocket bundle configuration

**NOTE** : Don't forget to clear your cache take in account the new file.

```yaml
gos_web_socket:
    ...
    server:
        host: 127.0.0.1
        port: 8080
        router:
            resources:
                - @AcmeBundle/Resources/config/pubsub/routing.yml
    ...
```

Create the route corresponding to your RPC

```yaml
acme_rpc:
    channel: sample/{method}
    handler:
        callback: 'acme.rpc' #related to the getName() or your RPC service
    requirements:
        method:
            pattern: "[a-z_]+" #accept all valid regex, don't put delimiters !
```

From here, each call who match with this pattern will handled by AcmeService.

**PRO TIP** : If you want use the same channel but refer to many service, you achieve this with the following example : 

```yaml
acme_a_b_c_rpc:
    channel: sample/{method}
    handler:
        callback: 'acme.method_abc.rpc'
    requirements:
        method:
            pattern: "method_a|method_b|method_c"
            
acme_d_e_rpc:
    channel: sample/{method}
    handler:
        callback: 'acme.method_d_e.rpc'
    requirements:
        method:
            pattern: "method_d|method_e"
```

_For more information on the Client Side of Gos WebSocket, please see [Client Side Setup](ClientSetup.md)_
