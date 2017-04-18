Gos Web Socket Bundle
=====================

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/GeniusesOfSymfony/WebSocketBundle?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge) [![Latest Stable Version](https://poser.pugx.org/gos/web-socket-bundle/v/stable)](https://packagist.org/packages/gos/web-socket-bundle) [![Total Downloads](https://poser.pugx.org/gos/web-socket-bundle/downloads)](https://packagist.org/packages/gos/web-socket-bundle) [![License](https://poser.pugx.org/gos/web-socket-bundle/license)](https://packagist.org/packages/gos/web-socket-bundle) [![Build Status](https://scrutinizer-ci.com/g/GeniusesOfSymfony/WebSocketBundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/GeniusesOfSymfony/WebSocketBundle/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GeniusesOfSymfony/WebSocketBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/GeniusesOfSymfony/WebSocketBundle/?branch=master)
![Websocket](ws_logo.jpeg)

About
------
Gos Web Socket is a Symfony2 Bundle designed to bring together WebSocket functionality in a easy to use application architecture.

Much like Socket.IO it provides both server side and client side code ensuring you have to write as little as possible to get your app up and running.

Powered By [Ratchet](http://socketo.me) and [Autobahn JS](http://autobahn.ws/js), with [Symfony2](http://symfony.com/)

**[Demo project](https://github.com/GeniusesOfSymfony/WebsocketAppDemo)**


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
* Remote procedure call
* User authentication through websocket
* Periodic call
* Origin checker
* Push (zmq, amqp)

Resources
----------
* [Installation Instructions](#installation-instructions)
* [Client Setup](Resources/docs/ClientSetup.md)
* [Server Side of RPC](Resources/docs/RPCSetup.md)
* [PubSub Topic Handlers](Resources/docs/TopicSetup.md)
* [Periodic Services](Resources/docs/PeriodicSetup.md)(functions to be run every x seconds with the IO loop.)
* [Session Management & User authentication](Resources/docs/SessionSetup.md)
* [Server Events](Resources/docs/Events.md)
* [Configuration Reference](Resources/docs/ConfigurationReference.md)
* [Ship in production](Resources/docs/ShipInProduction.md)
* [Performance Bench](Resources/docs/Performance.md)
* [Push integration](Resources/docs/Pusher.md)
* [SSL configuration](Resources/docs/Ssl.md)

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
-------------------------

### Step 1: Install via composer

`composer require gos/web-socket-bundle`

### Step 2: Add to your App Kernel

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Gos\Bundle\WebSocketBundle\GosWebSocketBundle(),
        new Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle(),
    );
}
```
### Step 3: Configure WebSocket Server

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

For Symfony 2.7 & 2.8

```command
php app/console gos:websocket:server
```

For Symfony >3.x

```command
php bin/console gos:websocket:server
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
