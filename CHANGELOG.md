# Changelog

## 3.9.0 (????-??-??)

- Add support for configuring the ping interval for ping services
- Deprecate public access to public container services
- Deprecated the `setTimeout` method of periodic classes

## 3.8.1 (2021-06-16)

- Fixes for IP blocker service

## 3.8.0 (2021-06-16)

- Add support for `doctrine/cache` 2.0
- Bump minimum supported `doctrine/dbal` versions
- Add the ability to block IP addresses from connecting to the websocket server
- Deprecated the `Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent` class and corresponding event, subscribe to `Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent` instead

## 3.7.2 (2021-04-25)

- Symfony 5.3 compatibility fixes

## 3.7.1 (2021-03-29)

- Fix incorrect service reference

## 3.7.0 (2021-03-28)

- Drop support for Symfony 5.1 (Branch is EOL)
- Deprecated the `WS` class and `Socket` global variable in the `websocket.js` file, use the new static `GosSocket.connect()` method as a replacement for `WS.connect()` and store the singleton within your application if necessary
- Deprecated the `Gos\Bundle\WebSocketBundle\Server\EntryPoint` class, use the `Gos\Bundle\WebSocketBundle\Server\ServerLauncher` class instead
- Deprecated `Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface`, `Gos\Bundle\WebSocketBundle\Topic\PushableTopicInterface`,`Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface::onPush()`, and `Gos\Bundle\WebSocketBundle\Server\App\WampApplication::onPush()`; this is a continuation of the deprecation of the pusher services which was not previously flagged
- Deprecated `Gos\Bundle\WebSocketBundle\Topic\TopicManager::setWampApplication()`, as of 4.0 the dependency will be injected through the class' constructor

## 3.6.2 (2021-01-18)

- Reverted the change in 3.6.1

## 3.6.1 (2021-01-12)

- [#422](https://github.com/GeniusesOfSymfony/WebSocketBundle/pull/422) Periodic timer becomes broken when all topic subscribers closed their connections

## 3.6.0 (2020-11-27)

- Deprecated the requirement to implement `Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface::dispatch()`, the method will be removed from the interface in 4.0
- [MINOR B/C BREAK] Added type checks to the `$topic` argument of `Gos\Bundle\WebSocketBundle\Topic\TopicManager::getTopic()`, an `InvalidArgumentException` will now be thrown if a non-supported type is provided

## 3.5.0 (2020-11-02)

- Drop support for Symfony 5.0 (Branch is EOL)
- Allow install on PHP 8
- Updates for changes introduced in `doctrine/dbal` 2.11

## 3.4.0 (2020-08-26)

- Deprecated support for the `gos/websocket-client` package, use `ratchet/pawl` instead
- Deprecated the `Gos\Bundle\WebSocketBundle\Client\Driver\DoctrineCacheDriverDecorator`, if using the `doctrine/cache` package a `Gos\Bundle\WebSocketBundle\Client\Driver\SymfonyCacheDriverDecorator` using a `Symfony\Component\Cache\DoctrineProvider` instance can be used
- Introduced `Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface::removeAllClients()`, if implemented a client store can remove all clients from storage; in 4.0, this method will be required on all interface implementations
- Introduced `Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface::clear()`, if implemented a driver can remove all data from storage; in 4.0, this method will be required on all interface implementations
- `Gos\Bundle\WebSocketBundle\EventListener\StartServerListener` will now support `SIGTERM` signals as well as `SIGINT`

## 3.3.0 (2020-07-06)

- Change `Gos\Bundle\WebSocketBundle\Periodic\DoctrinePeriodicPing` to address the deprecation of `Doctrine\DBAL\Driver\PingableConnection`
- Deprecate support for `Doctrine\DBAL\Driver\PingableConnection` implementations in `Gos\Bundle\WebSocketBundle\Periodic\DoctrinePeriodicPing`, in 4.0 `Doctrine\DBAL\Connection` instances will be required
- Add aliases to bundle events to allow registering listeners using the FQCN
- Deprecated `Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent::setException()`, in 4.0 a `Throwable` instance will be a required constructor argument
- Deprecated `Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent::getException()`, use `Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent::getThrowable()` instead
- Remove call to `Ratchet\Wamp\Topic::broadcast()` if the dispatch method fails, see [the security advisory](https://github.com/GeniusesOfSymfony/WebSocketBundle/security/advisories/GHSA-wwgf-3xp7-cxj4) for additional details

## 3.2.0 (2020-06-01)

- Extend `Gos\Component\WebSocketClient\Wamp\ClientFactoryInterface` inside `Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampConnectionFactoryInterface`
- Added new `gos_web_socket.websocket_client` configuration node to configure a `Gos\Component\WebSocketClient\Wamp\ClientInterface` instance

## 3.1.0 (2020-05-31)

- Use the `symfony/deprecation-contracts` package to trigger runtime deprecation notices
- Deprecated `Gos\Bundle\WebSocketBundle\Pusher\PusherInterface` and `Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface`, and all related services, in favor of the Symfony Messenger component
- Removed `Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface::setStorageDriver()`, this method should no longer be relied on
- [MINOR B/C BREAK] Changed the (final) `Gos\Bundle\WebSocketBundle\Client\ClientStorage` constructor to require a `Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface` instance as the first argument, this only affects users manually instantiating an instance of the storage class
- Deprecated unused `gos_web_socket.client.storage.prefix` configuration node and container parameter
- Address deprecations in marking configuration nodes, services, and service aliases deprecated in Symfony 5.1

## 3.0.0 (2020-04-02)

- Consult the UPGRADE guide for changes between 2.x and 3.0
