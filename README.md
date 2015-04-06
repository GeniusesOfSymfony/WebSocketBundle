Gos Web Socket Bundle
=====================

About
--------------
Gos Web Socket is a Symfony2 Bundle designed to bring together WebSocket functionality in a easy to use application architecture.

Much like Socket.IO it provides both server side and client side code ensuring you have to write as little as possible to get your app up and running.

Powered By [Ratchet](http://socketo.me) and [Autobahn JS](http://autobahn.ws/js), with [Symfony2](http://symfony.com/)

What can I do with this bundle
------------------------------

Make real time application like
* Chat Application
* Real time notification
* Browser games

More commonly, all application who meet real time.

Built in feature
-----------------

* PHP Websocket server (IO / WAMP)
* PHP Websocket client (IO / WAMP)
* JS Websocket client (IO / WAMP)
* PubSub (with routing)
* Remote procedure
* User authentication through websocket
* Periodic call
* Origin checker

Performance
-----------

Benchmarked with [https://github.com/observing/thor](https://github.com/observing/thor).

This benchmark was done on my personal computer without any specific tweak.  

- Linux Cinnamon 17.1 64-bit (Kernel 3.13.0-24 Generic)
- Intel Core i7-2670QM 2.20 Ghz x 4
- 8 Go Ram

### Websocket Bundle config :

```yaml
gos_web_socket:
    client:
        firewall: secured_area
        session_handler: @session.handler.pdo
        storage:
            driver: @gos_web_socket.client_storage.driver.predis
    shared_config: true
    server:
        host: notification.dev
        port: 1337
        origin_check: false
        router:
            resources:
                - @GosNotificationBundle/Resources/config/pubsub/websocket/notification.yml
                - @GosNotificationBundle/Resources/config/pubsub/websocket/notification_rpc.yml
    origins:
        - www.notification.dev
        - notification.dev

```

### Thor

```
thor --amount 10000 ws://127.0.0.1.dev:1337 -C 5000 -W 4 -M 100
```

- Create 5000 concurrent/parallel connections.
- Smash 10000 connections
- Spawn 4 workers.
- Send 100 messages per connection

```text
Online               98648 milliseconds
Time taken           98663 milliseconds
Connected            10000
Disconnected         0
Failed               0
Total transferred    25.57MB
Total received       1.93MB

Durations (ms):
                     min     mean     stddev  median max    
Handshaking          266     36869     16120   48627 50344  
Latency              0       0             1       0 14     

Percentile (ms):
                      50%     66%     75%     80%     90%     95%     98%     98%    100%   
Handshaking          48627   49467   49647   49741   49915   50050   50163   50204   50344  
Latency              0       0       1       1       1       1       1       1       14     

```

Resources
--------------
* [Installation Instructions](#installation-instructions)
* [Client Setup](Resources/docs/ClientSetup.md)
* [Server Side of RPC](Resources/docs/RPCSetup.md)
* [PubSub Topic Handlers](Resources/docs/TopicSetup.md)
* [Periodic Services](Resources/docs/PeriodicSetup.md)(functions to be run every x seconds with the IO loop.)
* [Session Management & User authentication](Resources/docs/SessionSetup.md)
* [Server Events](Resources/docs/Events.md)
* [Configuration Reference](Resources/docs/ConfigurationReference.md)

Code Cookbook
--------------
* [Sharing Config between Server and Client](Resources/docs/code/SharingConfig.md)

Overview
--------

You must achieve these following steps before send your first message through websocket.

1. Install the bundle
2. Create you first topic handler
3. Implement the client (Javascript)

Let's do it !

Installation Instructions
--------------

###Step 1: Install via composer

`composer require gos/web-socket-bundle`

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
    server:
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
Launching Ratchet WS Server on: 127.0.0.1:8080
```

This means the websocket server is now up and running ! 

**From here, only the websocket server is running ! That doesn't mean you can subscribe, publish, call. Follow next step to do it :)**

### Next Steps

For further documentations on how to use WebSocket, please continue with the client side setup.

* [Setup Client Javascript](Resources/docs/ClientSetup.md)

## Original Project

[https://github.com/JDare/ClankBundle](https://github.com/JDare/ClankBundle)
