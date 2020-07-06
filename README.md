GosWebSocketBundle
==================

[![Latest Stable Version](https://poser.pugx.org/gos/web-socket-bundle/v/stable.svg)](https://packagist.org/packages/gos/web-socket-bundle) [![Latest Unstable Version](https://poser.pugx.org/gos/web-socket-bundle/v/unstable.svg)](https://packagist.org/packages/gos/web-socket-bundle) [![Total Downloads](https://poser.pugx.org/gos/web-socket-bundle/downloads.svg)](https://packagist.org/packages/gos/web-socket-bundle) [![License](https://poser.pugx.org/gos/web-socket-bundle/license.svg)](https://packagist.org/packages/gos/web-socket-bundle) [![Build Status](https://scrutinizer-ci.com/g/GeniusesOfSymfony/WebSocketBundle/badges/build.png?b=3.x)](https://scrutinizer-ci.com/g/GeniusesOfSymfony/WebSocketBundle/build-status/3.x) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GeniusesOfSymfony/WebSocketBundle/badges/quality-score.png?b=3.x)](https://scrutinizer-ci.com/g/GeniusesOfSymfony/WebSocketBundle/?branch=3.x) ![Run Tests](https://github.com/GeniusesOfSymfony/WebSocketBundle/workflows/Run%20Tests/badge.svg?branch=3.x)

About
------
GosWebSocketBundle is a Symfony bundle designed to bring together websocket functionality in a easy to use application architecture.

Much like Socket.IO, it provides both a server and client implementation ensuring you have to write as little as possible to get your application up and running.

Powered By [Ratchet](http://socketo.me) and [Autobahn JS](http://autobahn.ws/js), with [Symfony](http://symfony.com/)

**[Demo project](https://github.com/GeniusesOfSymfony/WebsocketAppDemo)**

Support
-------

| Version | Status                           | Symfony Versions          | Documentation                                                                             |
| ------- | -------------------------------- | ------------------------- | ----------------------------------------------------------------------------------------- |
| 1.x     | **No Longer Supported**          | 2.3-2.8, 3.0-3.4, 4.0-4.4 | [View Docs](https://github.com/GeniusesOfSymfony/WebSocketBundle/tree/1.x/Resources/docs) |
| 2.x     | Bug Fixes Until December 1, 2020 | 3.4, 4.4                  | [View Docs](https://github.com/GeniusesOfSymfony/WebSocketBundle/tree/2.x/Resources/docs) |
| 3.x     | Actively Supported               | 4.4, 5.0-5.1              | [View Docs](https://github.com/GeniusesOfSymfony/WebSocketBundle/tree/3.x/docs)           |

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
* Push (amqp)

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
* [Websocket Client](Resources/docs/WebsocketClient.md)

Code Cookbook
-------------

* [Sharing Config between Server and Client](Resources/docs/code/SharingConfig.md)

Installation Instructions
-------------------------

### Step 1: Install via Composer

- If you are using Symfony 3.3 or older, you will need the 1.x version of this bundle
- If you are using Symfony 3.4 or 4.4, you should use the 2.x version of this bundle (note Symfony 4.0 thru 4.3 are no longer supported)
- If you are using Symfony 4.4 or 5.0, you should use the 3.x version of this bundle

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
