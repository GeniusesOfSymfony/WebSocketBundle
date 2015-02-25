#Session sharing and user authentication

Thanks to Ratchet its easy to get the shared info from the same website session. As per the
[Ratchet documentation](http://socketo.me/docs/sessions), you must use a session handler other than the native one,
such as [Symfony PDO Session Handler](http://symfony.com/doc/master/cookbook/configuration/pdo_session_storage.html).

Once this is setup you will have something similar to the following in your config.yml

```yaml
framework:
    ...
    session:
        handler_id: session.handler.pdo
```

This is what informs Symfony2 of what to use as the session handler.

Similarly, we can do the same thing with Gos WebSocket to enable a shared session.


```yaml
gos_web_socket:
    ...
    client:
        firewall: secured_area #can be an array of firewalls
        session_handler: @session.handler.pdo
```

By using the same value, we are using the same exact service in both the WebSocket server and the Symfony2 application.
If you experience any issues with the session being empty or not as expected. Please ensure that everything is connecting via
the same URL, so the cookies can be read.

User is directly authenticated against his firewall, anonymous users are allow.

**Anonymous user is represented by string, example : anon-54e3352d535d2**
**Authenticated user is represented by UserInterface object**

##Client storage

Each user connected to socket is persisted in our persistence layer. By default they are stored in php via SplStorage.

###Customize client storage

```yaml
gos_web_socket:
    client:
        firewall: secured_area
        session_handler: @session.handler.pdo
        storage:
            driver: @gos_web_scocket.client_storage.predis.driver
```

### Doctrine Cache Bundle as Client Storage Driver

We natively provide decorator for [DoctrineCacheBundle](https://github.com/doctrine/DoctrineCacheBundle) to decorate Cache provider into client storage driver.

Create redis cache provider

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
            alias: gos_web_socket.client_storage.driver.redis
```

Use it as driver for client storage.

```yaml
gos_web_socket:
    client:
        firewall: secured_area
        session_handler: @session.handler.pdo
        storage:
            driver: @gos_web_socket.client_storage.driver.redis
            decorator: @gos_web_socket.client_storage.doctrine.decorator
```

### Create your own Driver

I want create Redis Driver working with predis client. Predis client provided by [SncRedisBundle](https://github.com/snc/SncRedisBundle).

Configure my client

```yaml
snc_redis:
    clients:
        cache:
            type: predis
            alias: client_storage.driver #snc_redis.client_storage.driver
            dsn: redis://127.0.0.1/2
            logging: %kernel.debug%
            options:
                profile: 2.2
                connection_timeout: 10
                read_write_timeout: 30
```






For information on sharing the config between server and client, read the [Sharing Config](code/SharingConfig.md) Code Cookbook.

_For info on bootstrapping the session with extra information, check out the [Events](Events.md)_
