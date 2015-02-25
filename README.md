Gos Web Socket
=====================

About
--------------
Gos Web Socket is a Symfony2 Bundle designed to bring together WebSocket functionality in a easy to use application architecture.

Much like Socket.IO it provides both server side and client side code ensuring you have to write as little as possible to get your app up and running.

Powered By [Ratchet](http://socketo.me) and [Autobahn JS](http://autobahn.ws/js), with [Symfony2](http://symfony.com/)

Resources
--------------
* [Installation Instructions](#installation-instructions)
* [Client Javascript](Resources/docs/ClientSetup.md)
* [Server Side of RPC](Resources/docs/RPCSetup.md)
* [PubSub Topic Handlers](Resources/docs/TopicSetup.md)
* [Periodic Services](Resources/docs/PeriodicSetup.md)(functions to be run every x seconds with the IO loop.)
* [Session Management & User authentication](Resources/docs/SessionSetup.md)
* [Server Events](Resources/docs/Events.md)

Code Cookbook
--------------
* [Sharing Config between Server and Client](Resources/docs/code/SharingConfig.md)

Installation Instructions
--------------

###Step 1: Install via composer
Add the following to your composer.json

```javascript
{
    "require": {
        "gos/web-socket-bundle": "~0.1"
    }
}
```

Then update composer to install the new packages:
```command
php composer.phar update
```

###Step 2: Add to your App Kernel

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Gos\Bundle\WebSocketBundle\GosWebSocketBundle(),
    );
}
```
###Step 3: Configure WebSocket Server

Add the following to your app/config.yml

```yaml
# Web Socket Configuration
gos_web_socket:
    web_socket_server:
        port: 8080        #The port the socket server will listen on
        host: 127.0.0.1   #The host ip to bind to
```

_Note: when connecting on the client, if possible use the same values as here to ensure compatibility for sessions etc._

### Step 4: Launching the Server

The Server Side WebSocket installation is now complete. You should be able to run this from the root of your symfony installation.

```command
php app/console gos:server
```

If everything is successful, you will see something similar to the following:

```
Starting Gos WebSocket
Launching Ratchet WS Server on: *:8080
```

This means the websocket server is now up and running!

### Next Steps

For further documentations on how to use WebSocket, please continue with the client side setup.

* [Setup Client Javascript](Resources/docs/ClientSetup.md)

## Original Project

[https://github.com/JDare/ClankBundle](https://github.com/JDare/ClankBundle)
