GosWebSocketBundle
==================

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/GeniusesOfSymfony/WebSocketBundle?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge) [![Latest Stable Version](https://poser.pugx.org/gos/web-socket-bundle/v/stable)](https://packagist.org/packages/gos/web-socket-bundle) [![Latest Unstable Version](https://poser.pugx.org/gos/web-socket-bundle/v/unstable)](https://packagist.org/packages/gos/web-socket-bundle) [![Total Downloads](https://poser.pugx.org/gos/web-socket-bundle/downloads)](https://packagist.org/packages/gos/web-socket-bundle) [![License](https://poser.pugx.org/gos/web-socket-bundle/license)](https://packagist.org/packages/gos/web-socket-bundle) [![Build Status](https://travis-ci.org/GeniusesOfSymfony/WebSocketBundle.svg?branch=master)](https://travis-ci.org/GeniusesOfSymfony/WebSocketBundle) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GeniusesOfSymfony/WebSocketBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/GeniusesOfSymfony/WebSocketBundle/?branch=master)
![Websocket](ws_logo.jpeg)

About
------
GosWebSocketBundle is a Symfony bundle designed to bring together websocket functionality in a easy to use application architecture.

Much like Socket.IO, it provides both a server and client implementation ensuring you have to write as little as possible to get your application up and running.

Powered By [Ratchet](http://socketo.me) and [Autobahn JS](http://autobahn.ws/js), with [Symfony](http://symfony.com/)

**[Demo project](https://github.com/GeniusesOfSymfony/WebsocketAppDemo)**

To view the documentation for the 1.x versions of this bundle, please view the [1.x](https://github.com/GeniusesOfSymfony/WebSocketBundle/tree/1.x) branch of this repository.
To view the documentation for the 2.x versions of this bundle, please view the [master](https://github.com/GeniusesOfSymfony/WebSocketBundle/tree/master) branch of this repository.

What can I do with this bundle?
-------------------------------

Websockets are very helpful for applications which require live activity and updates, including:

* Chat applications
* Real time notifications
* Browser games

Built in features
-----------------

* PHP Websocket server (IO / WAMP)
* PHP Websocket client (IO / WAMP)
* JavaScript Websocket client (IO / WAMP)
* [PubSub Router](https://github.com/GeniusesOfSymfony/PubSubRouterBundle)
* Remote Procedure Calls
* User authentication
* Periodic calls
* Origin checker
* Push (zmq, amqp)

Resources
---------

* [Installation Instructions](#installation-instructions)
* [Client Setup](Resources/docs/ClientSetup.md)
* [RPC Handlers](Resources/docs/RPCSetup.md)
* [PubSub Topic Handlers](Resources/docs/TopicSetup.md)
* [Periodic Services](Resources/docs/PeriodicSetup.md)
* [Session Management & User authentication](Resources/docs/SessionSetup.md)
* [Server Events](Resources/docs/Events.md)
* [Configuration Reference](Resources/docs/ConfigurationReference.md)
* [Performance Bench](Resources/docs/Performance.md)
* [Push integration](Resources/docs/Pusher.md)
* [SSL configuration](Resources/docs/Ssl.md)

Code Cookbook
-------------

* [Sharing Config between Server and Client](Resources/docs/code/SharingConfig.md)

Installation Instructions
-------------------------

### Step 1: Install via Composer

If your application requires support for Symfony versions before 3.4, use the 1.x releases of this bundle. For Symfony 3.4 or newer, use the 2.x releases.

`composer require gos/web-socket-bundle`

### Step 2: Enable the bundle

If your application is based on the Symfony Standard structure, you will need to add the bundle and its dependency, the `GosPubSubRouterBundle`, to your `AppKernel` class' `registerBundles()` method.

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...

            new Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle(),
            new Gos\Bundle\WebSocketBundle\GosWebSocketBundle(),
        ];

        // ...
    }

    // ...
}
```

If your application is based on the Symfony Flex structure, the bundle should be automatically registered, otherwise you will need to add it and its dependency, the `GosPubSubRouterBundle`, to your `config/bundles.php` file.

```php
<?php

return [
    // ...

    Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle::class => ['all' => true],
    Gos\Bundle\WebSocketBundle\GosWebSocketBundle::class => ['all' => true],
];

```

### Step 3: Configure the bundle

The following is the minimum configuration necessary to use the bundle. If you are using the Symfony Standard structure, this will be added to your `app/config/config.yml` file. If you are using the Symfony Flex structure, this will be added to your `config/packages/gos_web_socket.yaml` file.

```yaml
gos_web_socket:
    server:
        port: 8080        # The port the socket server will listen on
        host: 127.0.0.1   # The host ip to bind to
```

### Step 4: Launching the server

With the bundle installed and configured, you can now launch the websocket server through your Symfony application's command-line console.

```bash
php bin/console gos:websocket:server
```

If everything is successful, you will see something similar to the following:

```sh
INFO      [websocket] Starting web socket
INFO      [websocket] Launching Ratchet on 127.0.0.1:8080 PID: 12345
```

Congratulations, your websocket server is now running. However, you will still need to add integrations to your application to fully use the bundle.

## Original Project

[https://github.com/JDare/ClankBundle](https://github.com/JDare/ClankBundle)
