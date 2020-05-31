# Changelog

## 3.1.0 (2020-05-31)

- Use the `symfony/deprecation-contracts` package to trigger runtime deprecation notices
- Deprecated `Gos\Bundle\WebSocketBundle\Pusher\PusherInterface` and `Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface`, and all related services, in favor of the Symfony Messenger component
- Removed `Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface::setStorageDriver()`, this method should no longer be relied on
- [MINOR B/C BREAK] Changed the (final) `Gos\Bundle\WebSocketBundle\Client\ClientStorage` constructor to require a `Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface` instance as the first argument, this only affects users manually instantiating an instance of the storage class
- Deprecated unused `gos_web_socket.client.storage.prefix` configuration node and container parameter
- Address deprecations in marking configuration nodes, services, and service aliases deprecated in Symfony 5.1

## 3.0.0 (2020-04-02)

- Consult the UPGRADE guide for changes between 2.x and 3.0
