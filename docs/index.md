# About GosWebSocketBundle

GosWebSocketBundle is a [Symfony](https://symfony.com/) bundle built on top of [Ratchet](http://socketo.me) and [Autobahn|JS](http://autobahn.ws/js) designed to bring together websocket functionality in an easy-to-use application architecture.

Much like Socket.IO, it provides both a websocket server and client implementation ensuring you have to write as little as possible to get your application up and running.

The bundle includes:

- PHP Websocket server (IO / WAMP) built on Ratchet
- JavaScript Websocket client (IO / WAMP) built on Autobahn|JS
- [PubSub Router](https://github.com/GeniusesOfSymfony/PubSubRouterBundle) integration
- RPC support
- Integration with Symfony's Security component to share user authentication with the web frontend
- Repeating periodic function calls

## Installation

To install this bundle, run the following [Composer](https://getcomposer.org/) command:

```bash
composer require gos/web-socket-bundle
```

### Register The Bundle

For an application using Symfony Flex the bundle should be automatically registered, but if not you will need to add it to your `config/bundles.php` file.

```php
<?php

return [
    // ...

    Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle::class => ['all' => true],
    Gos\Bundle\WebSocketBundle\GosWebSocketBundle::class => ['all' => true],
];
```

### Configure The Bundle

The following is the minimum configuration necessary to use the bundle.

```yaml
# config/packages/gos_web_socket.yaml
gos_web_socket:
    server:
        # The host IP address on the server which connections for the websocket server are accepted.
        host: 127.0.0.1

        # The port on the server which connections for the websocket server are accepted.
        port: 8080

        router:
            resources:
                -
                    resource: '%kernel.project_dir%/config/pubsub/websocket/*'
                    type: 'glob'
```

## Launching The Websocket Server

With the bundle installed and configured, you can now launch the websocket server through your Symfony application's command-line console.

```sh
php bin/console gos:websocket:server
```

If everything is successful, you will see something similar to the following:

```sh
INFO      [websocket] Starting web socket
INFO      [websocket] Launching Ratchet on 127.0.0.1:8080 PID: 12345
```

Congratulations, your websocket server is now running. However, you will still need to add integrations to your application to fully use the bundle.

## Next Steps

- [Creating RPC Handlers](rpc.md)
- [Creating Topics](topics.md)
- [Creating Periodic Functions](periodic.md)
- [Bundle Configuration](config.md)
- [Authenticating Users](authentication.md)
- [Securing the Websocket Server](securing-connections.md)
- [Subscribing to Bundle Events](events.md)
- [Using the JavaScript Client](javascript-client.md)
