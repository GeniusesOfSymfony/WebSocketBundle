# Authenticating Users (Legacy Authentication)

**NOTE** This guide covers the legacy authentication system which will be removed in GosWebSocketBundle 4.0, please see [this guide](authentication.md) for information on the new authentication system.

When a connection is opened to the websocket server, the user is authenticated against the firewall(s) you have configured the bundle to use from your application.

Note that according to the [Ratchet documentation](http://socketo.me/docs/sessions), not all session handlers are compatible with this system.

## Define a session handler service

To use session sharing, the session handler must be a service defined in your application. The below example creates a service which uses PDO as the session handler and re-uses the same connection opened by Doctrine.

```yaml
services:
    session.handler.pdo:
        class: Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
        arguments:
            - !service { class: PDO, factory: ['@database_connection', 'getWrappedConnection'] }
            - { lock_mode: 0 }
```

If using the PDO session handler, ensure you have [set up the database correctly](https://symfony.com/doc/current/doctrine/pdo_session_storage.html) as well.

## Configure the session handler

First, you will need to ensure the FrameworkBundle is configured to use the session handler service you've defined.

```yaml
# config/packages/framework.yaml
framework:
    session:
        handler_id: 'session.handler.pdo'
```

Next, you will need to ensure the GosWebSocketBundle is configured to use the same session handler.

```yaml
# config/packages/gos_web_socket.yaml
gos_web_socket:
    client:
        # The service ID of the session handler service used to read session data.
        session_handler: 'session.handler.pdo'

        # The name of the security firewall to load the authenticated user data for, can be an array of firewalls
        firewall: main
```

Important notes:

- You must ensure your application's sessions are set up to allow both the websocket server and your HTTP server to read the same session cookies, you may need to update the [`session.cookie_domain`](https://symfony.com/doc/current/reference/configuration/framework.html#cookie-domain) config for the FrameworkBundle to set the cookie domain correctly
- If you change the [`session.name`](https://symfony.com/doc/current/reference/configuration/framework.html#id6) config in the FrameworkBundle, you must also change the `session.name` parameter in your `php.ini`file for Ratchet to read the session data correctly

## Client Storage

The `Symfony\Component\Security\Core\Authentication\Token\TokenInterface` that all user info is derived from is stored in a bundle specific persistence layer, by default this is an in-memory storage layer.

### Customize client storage

The storage layer can be customized using the `gos_web_socket.client.storage` configuration key.

```yaml
# config/packages/gos_web_socket.yaml
gos_web_socket:
    client:
        # The service ID of the session handler service used to read session data.
        session_handler: 'session.handler.pdo'

        # The name of the security firewall to load the authenticated user data for, can be an array of firewalls
        firewall: main

        storage:
            # The service ID of the storage driver to use for storing connection data.
            driver: 'App\WebSocket\Client\Driver\PredisDriver'

            # The cache TTL (in seconds) for clients in storage.
            ttl: 28800

services:
    App\WebSocket\Client\Driver\PredisDriver:
        arguments:
            - '@Predis\Client'
```

In this example, the storage has been changed to a service defined in your application to use a Predis Client as the storage driver.

### Using `doctrine/cache` as a client storage driver

** NOTE ** This integration is deprecated and will be removed in 4.0, the Symfony Cache component should be used instead

A decorator is provided which allows for cache drivers from [Doctrine's Cache Library](https://www.doctrine-project.org/projects/cache.html) to be used as the client storage driver.

The below example is used to create a Redis cache provider:

```yaml
# config/packages/doctrine_cache.yaml
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
# config/packages/gos_web_socket.yaml
gos_web_socket:
    client:
        # The service ID of the session handler service used to read session data.
        session_handler: 'session.handler.pdo'

        # The name of the security firewall to load the authenticated user data for, can be an array of firewalls
        firewall: main

        storage:
            # The service ID of the storage driver to use for storing connection data.
            driver: 'app.doctrine_cache.websocket'

            # The service ID of a decorator for the client storage driver.
            decorator: 'gos_web_socket.client.driver.doctrine_cache'
```

**Note:** It is recommended to use a dedicated cache store for the client storage since the client storage can clear all entries in the backend storage

### Using `symfony/cache` as a client storage driver

A decorator is provided which allows for cache drivers from [Symfony's Cache Component](https://symfony.com/doc/current/components/cache.html) to be used as the client storage driver.

The below example is used to create a Redis cache provider:

```yaml
# config/packages/cache.yaml
framework:
    cache:
        default_redis_provider: redis://localhost
```

You can now use it as the driver for the client storage layer.

```yaml
# config/packages/gos_web_socket.yaml
gos_web_socket:
    client:
        # The service ID of the session handler service used to read session data.
        session_handler: 'session.handler.pdo'

        # The name of the security firewall to load the authenticated user data for, can be an array of firewalls
        firewall: main

        storage:
            # The service ID of the storage driver to use for storing connection data.
            driver: 'cache.adapter.redis'

            # The service ID of a decorator for the client storage driver.
            decorator: 'gos_web_socket.client.driver.symfony_cache'
```

**Note:** It is recommended to use a dedicated cache store for the client storage since the client storage can clear all entries in the backend storage

### Create your own driver

If need be, you can also create your own storage driver. All drivers must implement `Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface`.

## Retrieve authenticated user

Whenever the `Ratchet\ConnectionInterface` instance is available, you are able to retrieve the user account info using a `Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface` instance (by default, the `gos_web_socket.client.manipulator` service).

For example, inside an RPC handler:

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
    private ClientManipulatorInterface $clientManipulator;

    public function __construct(ClientManipulatorInterface $clientManipulator)
    {
        $this->clientManipulator = $clientManipulator;
    }

    /**
     * Adds the params together, if the user is authenticated.
     */
    public function sum(ConnectionInterface $connection, WampRequest $request, $params): array
    {
        $user = $this->clientManipulator->getClient($connection);

        if ($user instanceof UserInterface) {
            return ['result' => array_sum($params)];
        }

        return ['error' => true, 'msg' => 'You must be authenticated to use this function.'];
    }

    /**
     * Name of the RPC handler.
     */
    public function getName(): string
    {
        return 'acme.rpc';
    }
}
```

## Find the connection for a specific user

You can use the `findAllByUsername` method of the client manipulator to find all active connections for the given username.

```php
<?php

namespace App\Websocket\Topic;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

final class AcmeTopic implements TopicInterface
{
    private ClientManipulatorInterface $clientManipulator;

    public function __construct(ClientManipulatorInterface $clientManipulator)
    {
        $this->clientManipulator = $clientManipulator;
    }

    /**
     * Handles subscription requests for this topic.
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
    {
        // This will broadcast a message to all subscribers of this topic notifying them of the new subscriber.
        $topic->broadcast(['msg' => $connection->resourceId.' has joined '.$topic->getId()]);
    }

    /**
     * Handles unsubscription requests for this topic.
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
    {
        // This will broadcast a message to all subscribers of this topic notifying them of the unsubscribing user.
        $topic->broadcast(['msg' => $connection->resourceId.' has left '.$topic->getId()]);
    }

    /**
     * Handles publish requests for this topic.
     *
     * @param mixed $event The event data
     */
    public function onPublish(
        ConnectionInterface $connection,
        Topic $topic,
        WampRequest $request,
        $event,
        array $exclude,
        array $eligible
    ): void {
        if (!isset($event['username'])) {
            // Broadcast an error back to the publisher
            $topic->broadcast(
                ['error' => true, 'msg' => 'The username parameter is required.'],
                [],
                [$connection->WAMP->sessionId]
            );

            return;
        }

        $recipients = $this->clientManipulator->findAllByUsername($topic, $event['username']);

        if (!empty($recipients)) {
            $recipientIds = [];

            foreach ($recipients as $recipient) {
                $recipientIds[] = $recipient->getConnection()->WAMP->sessionId;
            }

            $topic->broadcast('message', [], $recipientIds);
        }
    }

    /**
     * Name of the topic.
     */
    public function getName(): string
    {
        return 'acme.topic';
    }
}
```
