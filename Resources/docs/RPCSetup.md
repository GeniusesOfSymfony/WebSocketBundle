#Remote Procedure Call Setup

Every remote procedure call (RPC) in Gos WebSocket has its own "network namespace" in order to dispatch requests to the correct command.

In Symfony RPCs are setup as services. This allows you full control of what to do with the class, whether its a mailer or an entity manager.

If you are new to services, please see [Symfony2: Service Container](http://symfony.com/doc/master/book/service_container.html)

##Step 1: Create the Service Class

```php
<?php

namespace Acme\HelloBundle\RPC;

use Ratchet\ConnectionInterface as Conn;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;

class AcmeService implements RpcInterface
{
    /**
     * Adds the params together
     *
     * Note: $conn isnt used here, but contains the connection of the person making this request.
     *
     * @param \Ratchet\ConnectionInterface $conn
     * @param array $params
     * @return int
     */
    public function addFunc(Conn $conn, $params)
    {
        return array("result" => array_sum($params));
    }
    
    /**
     * prefix of RPC
     * e.g will give sample/add_func
     * 
     * @return string
     */
    public function getPrefix()
    {
        return 'sample';
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
    //this will call the server side function "AcmeService::addFunc"
    session.call("sample/add_func", [2, 5])
    ...
```

To use the "other" network namespace (the one commented out in the config), we would use:

```javascript
    session.call("other/the_function_name", params);
```

If we added a function called "subFunc" to AcmeService, which returned all the parameters subtracted from each other, we could call it via:

```javascript
    //this will call the server side function "AcmeService::subFunc"
    session.call("sample/sub_func", [10, 3])
    ...
```

The idea of having these network namespaces is to group relevant code into separate files.

_For more information on the Client Side of Gos WebSocket, please see [Client Side Setup](ClientSetup.md)_
