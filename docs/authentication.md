# Authenticating Users

The websocket bundle automatically handles authenticating users on every connection to your websocket server, allowing your [RPC](rpc.md) and [Topic](topics.md) classes to use your authenticated user data.

## Enabling the Authenticator API

The following is the minimum configuration needed to enable the authenticator and its services:

```yaml
gos_web_socket:
    authentication:
        enable_authenticator: true
```

This instructs the bundle to enable the new authentication API and replace the [legacy implementation](auth.md). When this system is enabled, the legacy services are removed from the service container and should not be used due to incompatibilities between the two authentication APIs.

*NOTE* This configuration is needed as a temporary migration step, as of GosWebSocketBundle 4.0 this will be the authentication API.

However, this will not enable any authentication providers on its own, you must also configure the provider(s) your application will use for authentication.

## Authentication Providers

An authentication provider is an implementation of `Gos\Bundle\WebSocketBundle\Authentication\Provider\AuthenticationProviderInterface` which processes a `Ratchet\ConnectionInterface` and creates a security token representing the current user.

A provider is required to have two methods:

- `supports()` - Determines if the provider can authenticate the given connection
- `authenticate()` - Authenticates the connection

### Session Authentication

The bundle provides a session authentication provider which will authenticate users using their HTTP session from your website's frontend.

To enable the session authenticator, you must add it to the `providers` list in the authentication configuration and configure the session handler that will be used. In this example, your Symfony application will use the PDO session handler.

```yaml
services:
    session.handler.pdo:
        class: Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
        arguments:
            - !service { class: PDO, factory: ['@database_connection', 'getWrappedConnection'] }
            - { lock_mode: 0 }

framework:
    session:
        handler_id: '@session.handler.pdo'

gos_web_socket:
    authentication:
        enable_authenticator: true
        providers:
            session:
                session_handler: '@session.handler.pdo'
```

Configuring the session handler will add the [`SessionProvider` component](http://socketo.me/docs/sessions) to the websocket server which will provide a read-only interface for the session data from your website. Note that there are some restrictions on the session handlers you can use, please see the linked documentation for more information.

By default, the session authentication provider will attempt to authenticate to any of the firewalls set in your `security.firewalls` configuration in the same order which the firewalls are defined. You can specify the firewall(s) to use with the `firewall` configuration key on the session provider.

```yaml
gos_web_socket:
    authentication:
        enable_authenticator: true
        providers:
            session:
                firewalls: ['main'] # This can be an array to specify multiple firewalls or a string when specifying a single firewall 
                session_handler: '@session.handler.pdo'
```

### Provider Priority

When providers are registered to the authenticator service, they are then used in a "first in, first out" order, meaning the order they are triggered will be the same order they are configured in. Assuming your application has multiple authenticators and you want a custom authenticator to be attempted before the session authenticator, you would use the below configuration to do so:

```yaml
gos_web_socket:
    authentication:
        enable_authenticator: true
        providers:
            custom: ~
            session: ~
```

### Registering New Authenticators

In addition to creating a class implementing `Gos\Bundle\WebSocketBundle\Authentication\Provider\AuthenticationProviderInterface`, you must also register the authenticator with a `Gos\Bundle\WebSocketBundle\DependencyInjection\Factory\Authentication\AuthenticationProviderFactoryInterface` to the bundle's container extension. Similar to factories used by Symfony's `SecurityBundle`, this factory is used to allow you to configure the authenticator for your application and build the authentication provider service. 

A factory is required to have two methods:

- `getKey()` - A unique name to identify the provider in the application configuration, this name is used as the key in the `providers` list
- `addConfiguration()` - Defines the configuration nodes (if any are required) for the authenticator
- `createAuthenticationProvider()` - Registers the authentication provider service to the dependency injection container and returns the provider's service ID

The factory must be registered to this bundle's container extension when the container is being built. Typically, this would be in the `build()` method of your application's `Kernel` or a bundle's `Bundle` class.

```php
<?php

namespace App;

use App\DependencyInjection\Factory\Authentication\CustomAuthenticationProviderFactory;
use Gos\Bundle\WebSocketBundle\DependencyInjection\GosWebSocketExtension;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        /** @var GosWebSocketExtension $extension */
        $extension = $container->getExtension('gos_web_socket');
        $extension->addAuthenticationProviderFactory(new CustomAuthenticationProviderFactory());
    }
}
```

## Storing Tokens

After authentication is complete, the token is stored in a `Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface` instance. The default implementation uses a `Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver\StorageDriverInterface` as an abstraction layer for where authentication tokens are stored.

By default, the bundle uses an in-memory storage driver. The storage driver can be configured with the `storage` section of the authentication configuration.

### In-Memory Storage

The below example represents the default configuration for the in-memory storage driver.

```yaml
gos_web_socket:
    authentication:
        storage:
            type: in_memory
```

### Cache Storage

A cache pool can be used as a storage driver by setting the storage type to `psr_cache` and specifying the cache pool that should be used.

*NOTE* Unlike the legacy authentication system, there are no options to configure a storage TTL or cache prefix in the bundle configuration. These should be set on your cache pool if desired.

```yaml
gos_web_socket:
    authentication:
        storage:
            type: psr_cache
            pool: 'cache.websocket'
```

### Service Storage

You can create your own implementation of the storage driver interface and use that service by setting the storage type to `service` and specifying the container service ID to use.

```yaml
gos_web_socket:
    authentication:
        storage:
            type: storage
            id: 'app.websocket.storage.driver'
```

## Fetching Tokens

The `Gos\Bundle\WebSocketBundle\Authentication\ConnectionRepositoryInterface` provides several helper methods for querying the token storage to find the connections and tokens for any connected user. For example, this repository could be used to find all authenticated users connected to a given topic to send a message.

### Token Connection DTO

The `Gos\Bundle\WebSocketBundle\Authentication\TokenConnection` object is a DTO which is returned by many of the repository methods and contains the `Ratchet\ConnectionInterface` and its security token from the authenticator. 

### Retrieving All Connections For A Topic

The `findAll()` method is used to find all connections for a given topic. The method has an optional `$anonymous` parameter which can be used to filter out connections for unauthenticated users. The list will be returned as an array of `Gos\Bundle\WebSocketBundle\Authentication\TokenConnection` objects.

### Retrieving All Connections For A Username

The `findAllByUsername()` method is used to find all connections for a user with the given username. This is helpful if a user has multiple active connections (i.e. has multiple tabs in their browser open). The list will be returned as an array of `Gos\Bundle\WebSocketBundle\Authentication\TokenConnection` objects.

### Retrieving All Connections For A User With A Role

The `findAllWithRoles()` method is used to find all connections for a user who has any of the given roles. Note that this method checks the list of roles on the underlying security token and does not use the site's role hierarchy. The list will be returned as an array of `Gos\Bundle\WebSocketBundle\Authentication\TokenConnection` objects.

### Retrieving The Token For A Connection

The `findTokenForConnection()` method is used to find the security token for the given connection.

### Retrieving The User For A Connection

The `getUser()` method is used to retrieve the user for the given connection. This is a shortcut for `$repository->findTokenForConnection($token)->getUser()`.

## Migrating from the Legacy Authentication API

To update your application to use the new authentication API, you will need to make the following changes:

1) Enable the new API

The below is the minimal configuration necessary with notes regarding migrating from the legacy configuration:

```yaml
gos_web_socket:
    authentication:
        enable_authenticator: true
        providers:
            session:
                firewall: ~ # This should match the `gos_web_socket.client.firewall` config value
                session_handler: '@session.handler.pdo' # This should match the `gos_web_socket.client.session_handler` config value
```

If you are using the client storage with a Symfony cache decorator, you can migrate this configuration to the new storage by moving your config values from the `client` section to the `authentication` section

Example Legacy Configuration:

```yaml
gos_web_socket:
    client:
        storage:
            driver: 'cache.websocket'
            decorator: 'gos_web_socket.client.driver.symfony_cache'
```

Example New Configuration:

```yaml
gos_web_socket:
    authentication:
        storage:
            type: psr_cache
            pool: 'cache.websocket'
```

2) Replace class and service references

The new authentication API is a full replacement for the legacy implementation. When enabled, the legacy implementation cannot be used.

The below table shows an approximate map of the legacy interfaces to the new interfaces alongside their purposes:

| Legacy API                                                                        | New API                                                                           | Purpose                                                                                                            |
| --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| `Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface` | `Gos\Bundle\WebSocketBundle\Authentication\AuthenticatorInterface`                | Authenticates the given connection to the server                                                                   |
| `Gos\Bundle\WebSocketBundle\Client\ClientConnection`                              | `Gos\Bundle\WebSocketBundle\Authentication\TokenConnection`                       | DTO holding the connection and security token                                                                      |
| `Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface`                    | `Gos\Bundle\WebSocketBundle\Authentication\ConnectionRepositoryInterface`         | Queries the connection storage to find connections and tokens                                                      |
| `Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface`                        | `Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface`         | Manages the token storage for the websocket server                                                                 |
| `Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface`                        | `Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver\StorageDriverInterface` | Storage layer for security tokens for the websocket server (typically only implemented for custom storage drivers) |

The below table shows an approximate map of the legacy service IDs to the new service IDs:

| Legacy Service                                            | New Service                                           | Purpose                                                       |
| --------------------------------------------------------- | ----------------------------------------------------- | ------------------------------------------------------------- |
| `gos_web_socket.client.authentication.websocket_provider` | `gos_web_socket.authentication.authenticator`         | Authenticates the given connection to the server              |
| `gos_web_socket.client.manipulator`                       | `gos_web_socket.authentication.connection_repository` | Queries the connection storage to find connections and tokens |
| `gos_web_socket.client.storage`                           | `gos_web_socket.authentication.token_storage`         | Manages the token storage for the websocket server            |
