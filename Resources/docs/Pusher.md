# Pusher & Push Handler

The GosWebSocketBundle contains a system loosely similar to [Symfony's Messenger component](https://symfony.com/doc/current/components/messenger.html) which allows you to send and receive messages with third party systems.

Supported integrations include:

* AMQP (pusher & push handler)
* WAMP (pusher)
* (_deprecated_) ZMQ (pusher & push handler)

## AMQP Pusher

The AMQP Pusher allows you to send and receive messages using an AMQP compliant system, such as RabbitMQ.

### Extra Requirements

* The [`amqp`](https://pecl.php.net/package/amqp) extension for PHP (`pecl install amqp`)
* The [`gos/react-amqp`](https://github.com/GeniusesOfSymfony/ReactAMQP) Composer package (`composer require gos/react-amqp`)

### Configuration

To use the AMQP pusher, you will need to enable it in the bundle's configuration. For an application based on the Symfony Standard structure, you will need to update your `app/config/config.yml` file. For an application based on Symfony Flex, use the `config/packages/gos_web_socket.yaml` file.

```yaml
gos_web_socket:
    pushers:
        amqp:
            default: false # Unused
            host: 127.0.0.1 # Host address for the AMQP server
            port: 5672 # Port the AMQP server is listening on
            login: ~ # Required, the login for the AMQP server
            password: ~ # Required, the password for the AMQP server
            vhost: / # The virtual host on the host, default `/`
            read_timeout: 0 # Timeout for incoming activity in seconds, default 0
            write_timeout: 0 # Timeout for outgoing activity in seconds, default 0
            connect_timeout: 0 # Connection timeout in seconds, default 0
            queue_name: gos_websocket # The name of the queue for messages, default `gos_websocket`
            exchange_name: gos_websocket_exchange # The name of the exchange for messages, default `gos_websocket`
```

## WAMP Pusher

The WAMP pusher allows you to push a message to your websocket server using the [`gos/websocket-client`](https://github.com/GeniusesOfSymfony/WebSocketPhpClient) library.

### Configuration

To use the WAMP pusher, you will need to enable it in the bundle's configuration. For an application based on the Symfony Standard structure, you will need to update your `app/config/config.yml` file. For an application based on Symfony Flex, use the `config/packages/gos_web_socket.yaml` file.

```yaml
gos_web_socket:
    pushers:
        wamp:
            host: 127.0.0.1 # This will probably be the same as your `gos_web_socket.server.host` value
            port: 80 # This will probably be the same as your `gos_web_socket.server.port` value
            ssl: false # Flag to enable SSL connections to the websocket server, default false
            origin: null # The origin domain for the pusher, default null (if origin checking is enabled on your websocket server, this value must be allowed)
```

Note the bundle only provides a `Gos\Bundle\WebSocketBundle\Pusher\PusherInterface` implementation for WAMP, the equivalent for `Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface` on WAMP is your websocket server. Also be aware that when the pusher is used to send a message to a Topic, your topic's `onPublish` method is triggered versus `onPush` for WAMP connections.

## ZMQ Pusher

The ZMQ Pusher allows you to send and receive messages using an ZMQ compliant system.

**NOTE:** Support for ZMQ is deprecated and removed in 2.0 

### Extra Requirements

* The [`zmq`](https://pecl.php.net/package/zmq) extension for PHP (`pecl install zmq`)
* The [`react/zmq`](https://github.com/friends-of-reactphp/zmq) Composer package (`composer require react/zmq`)

### Configuration

To use the ZMQ pusher, you will need to enable it in the bundle's configuration. For an application based on the Symfony Standard structure, you will need to update your `app/config/config.yml` file. For an application based on Symfony Flex, use the `config/packages/gos_web_socket.yaml` file.

```yaml
gos_web_socket:
    pushers:
        zmq:
            default: false # Unused
            host: 127.0.0.1 # Host address for the ZMQ server
            port: 5555 # Port the ZMQ server is listening on
            persistent: true # Flag indicating the current context is persistent, default `true`
            protocol: tcp # The protocol to use for the connection, default `tcp`
            linger: -1 # Specifies how long the socket blocks trying flush messages after it has been closed, default -1
```

## Using a pusher

Depending on the integration(s) in your application, you will need to retrieve the appropriate service from the service container.

* AMQP - `gos_web_socket.amqp.pusher`
* WAMP - `gos_web_socket.wamp.pusher`
* ZMQ - `gos_web_socket.zmq.pusher`

The below example demonstrates pushing a message using the WAMP pusher from a controller after updating a record in the database.

```php
<?php

use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampPusher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class PostController extends AbstractController
{
    public function update(Request $request)
    {
        // Do stuff...

        /** @var PusherInterface $pusher */
        $pusher = $this->get('gos_web_socket.wamp.pusher');

        $pusher->push($messageData, $routeName, $routeParameters, $context);
    }

    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                'gos_web_socket.wamp.pusher' => WampPusher::class,
            ]
        );
    }

}
```

The following data is required when calling `Gos\Bundle\WebSocketBundle\Pusher\PusherInterface::push()`:

* `$messageData` is the data to be sent with the message
* `$routeName` is the name of the route which should receive the message
* `$routeParameters` is an array of parameters required to route the message to the `$routeName`
* `$context` is an array of extra context information for the pusher (presently only the AMQP pusher uses this)

## Pusher events

The server push handlers will dispatch events when a message succeeds or fails, allowing your application to hook these handlers with extra logic.

When pusher send message or fail to send it, we dispatch event to allow you to plug your own logic.

* `gos_web_socket.push_success` is dispatched when a server push succeeds
* `gos_web_socket.push_fail` is dispatched when a server push fails

For both events, a `Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent` object is sent to event listeners.

## Custom pushers

For advanced use cases, you can also create your own integrations.

For a custom pusher, your service must implement `Gos\Bundle\WebSocketBundle\Pusher\PusherInterface` and be tagged with the `gos_web_socket.pusher` service tag.

For a custom server push handler, your service must implement `Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface` and be tagged with the `gos_web_socket.push_handler` service tag.
