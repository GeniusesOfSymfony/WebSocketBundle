# Session Sharing and User Authentication

Thanks to Ratchet, it's easy to get the user info from the session your website's visitors create. As per the [Ratchet documentation](http://socketo.me/docs/sessions), not all session handlers are compatible with this system.

## Define a session handler service

To use session sharing, the session handler must be a service defined in your application. The below example creates a service which uses PDO as the session handler and re-uses the same connection opened by Doctrine.

```yaml
session.handler.pdo:
    class: Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
    arguments:
        - !service { class: PDO, factory: 'database_connection:getWrappedConnection' }
        - { lock_mode: 0 }

```

If using the PDO session handler, ensure you have [set up the database correctly](https://symfony.com/doc/current/doctrine/pdo_session_storage.html) as well.

## Configure the session handler

First, you will need to ensure the FrameworkBundle is configured to use the session handler service you've defined. For Symfony Standard based applications, you should update the `app/config/config.yml` file. For Symfony Flex applications, you should update `config/packages/framework.yaml`.

```yaml
framework:
    session:
        handler_id: 'session.handler.pdo'
```

Next, you will need to ensure the GosWebSocketBundle is configured to use the same session handler. For Symfony Standard based applications, you should update the `app/config/config.yml` file. For Symfony Flex applications, you should update `config/packages/gos_web_socket.yaml`.

```yaml
gos_web_socket:
    client:
        firewall: main # Can be an array of firewalls
        session_handler: 'session.handler.pdo'
```

**Note:** You must ensure your application's sessions are set up correctly to allow both the websocket server and your HTTP server to read the same session cookies.

When a connection is opened to the websocket server, the user is authenticated against the firewall(s) you have configured the bundle to use from your application.

Similar to the `getUser()` method in controllers, an anonymous user is represented as a string and an authenticated user is represented as a `Symfony\Component\Security\Core\User\UserInterface` object (typically the User entity or data object you have configured).

## Client Storage

The `Symfony\Component\Security\Core\Authentication\Token\TokenInterface` that all user info is derived from is stored in a bundle specific persistence layer, by default this is an in-memory storage layer.

### Customize client storage

The storage layer can be customized using the `gos_web_socket.client.storage` configuration key. For Symfony Standard based applications, you should update the `app/config/config.yml` file. For Symfony Flex applications, you should update `config/packages/gos_web_socket.yaml`.

```yaml
gos_web_socket:
    client:
        firewall: main
        session_handler: 'session.handler.pdo'
        storage:
            driver: 'app.websocket.client_storage.predis'
            ttl: 28800 # Optional, time to live if you use a compatible cache driver
            prefix: client # Optional, key prefix if you use a compatible cache driver, creates key as "client:1" instead of "1"

services:
    app.websocket.client_storage.predis:
        class: Gos\Bundle\WebSocketBundle\Client\Driver\PredisDriver
        arguments:
            - '@Predis\Client'
            - '%web_socket_server.client_storage.prefix'
```

In this example, the storage has been changed to a service defined in your application to use a Predis Client as the storage driver.

### Using `doctrine/cache` as a client storage driver

A decorator is provided which allows for cache drivers from [Doctrine's Cache Library](https://www.doctrine-project.org/projects/cache.html) to be used as the client storage driver.

The below example is used to create a Redis cache provider:

```yaml
doctrine_cache:
    providers:
        redis_cache:
            redis:
                host: 127.0.0.1
                port: 6379
                database: 3
        websocket_cache_client:
            type: redis
            alias: app.doctrine_cache.websocket
```

You can now use it as the driver for the client storage layer.

```yaml
gos_web_socket:
    client:
        firewall: main
        session_handler: 'session.handler.pdo'
        storage:
            driver: 'app.doctrine_cache.websocket' # The service which should be decorated
            decorator: 'gos_web_socket.client_storage.doctrine.decorator' # The decorator to apply to the driver
```

### Using `symfony/cache` as a client storage driver

A decorator is provided which allows for cache drivers from [Symfony's Cache Component](https://symfony.com/doc/current/components/cache.html) to be used as the client storage driver.

The below example is used to create a Redis cache provider:

```yaml
framework:
    cache:
        default_redis_provider: redis://localhost
```

You can now use it as the driver for the client storage layer.

```yaml
gos_web_socket:
    client:
        firewall: main
        session_handler: 'session.handler.pdo'
        storage:
            driver: 'cache.adapter.redis' # The service which should be decorated
            decorator: 'gos_web_socket.client_storage.symfony.decorator' # The decorator to apply to the driver
```

### Create your own driver

If need be, you can also create your own storage driver. All drivers must implement `Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface`.

## Retrieve authenticated user

Whenever the `Ratchet\ConnectionInterface` instance is available, you are able to retrieve the user account info using a `Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface` instance (by default, the `gos_web_socket.websocket.client_manipulator` service).

For example inside a RPC handler:

```php
<?php

namespace App\Websocket\Rpc;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AcmeRpc implements RpcInterface
{
    /**
     * @var ClientManipulatorInterface
     */
    private $clientManipulator;

    /**
     * @param ClientManipulatorInterface $clientManipulator
     */
    public function __construct(ClientManipulatorInterface $clientManipulator)
    {
        $this->clientManipulator = $clientManipulator;
    }

    /**
     * Adds the params together, if the user is authenticated
     *
     * @param ConnectionInterface $connection
     * @param WampRequest $request
     * @param array $params
     *
     * @return array
     */
    public function sum(ConnectionInterface $connection, WampRequest $request, $params)
    {
        $user = $this->clientManipulator->getClient($connection);

        if ($user instanceof UserInterface) {
            return ['result' => array_sum($params)];
        }

        return ['error' => true, 'msg' => 'You must be authenticated to use this function.'];
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

## Find the connection for a specific user

You can use the `findByUsername` method of the client manipulator to find a connection for the given username.

```php
<?php

namespace App\Websocket\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

final class AcmeTopic implements TopicInterface
{
    /**
     * @var ClientManipulatorInterface
     */
    private $clientManipulator;

    /**
     * @param ClientManipulatorInterface $clientManipulator
     */
    public function __construct(ClientManipulatorInterface $clientManipulator)
    {
        $this->clientManipulator = $clientManipulator;
    }

    /**
     * This will receive any Subscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     *
     * @return void
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        // This will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast(['msg' => $connection->resourceId.' has joined '.$topic->getId()]);
    }

    /**
     * This will receive any unsubscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     *
     * @return void
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        // This will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast(['msg' => $connection->resourceId.' has left '.$topic->getId()]);
    }

    /**
     * This will receive any Publish requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @param mixed $event
     * @param array $exclude
     * @param array $eligibles
     *
     * @return mixed
     */
    public function onPublish(
        ConnectionInterface $connection,
        Topic $topic,
        WampRequest $request,
        $event,
        array $exclude,
        array $eligible
    ) {
        if (!isset($event['username'])) {
            // Broadcast an error back to the publisher
            $topic->broadcast(
                ['error' => true, 'msg' => 'The username parameter is required.'],
                [],
                [$connection->WAMP->sessionId]
            );

            return;
        }

        $recipient = $this->clientManipulator->findByUsername($topic, $params['username']);

        // Check if a connection was found, this will be false if the user is not connected
        if ($recipient !== false) {
            $topic->broadcast('message', [], [$recipient['connection']->WAMP->sessionId]);
        }
    }

    /**
     * Like RPC the name is used to identify the channel
     *
     * @return string
     */
    public function getName()
    {
        return 'acme.topic';
    }
}
```

For information on sharing the config between server and client, read the [Sharing Config](code/SharingConfig.md) Code Cookbook.

_For info on bootstrapping the session with extra information, check out the [Events](Events.md)_
