#Session sharing and user authentication

Thanks to Ratchet its easy to get the shared info from the same website session. As per the
[Ratchet documentation](http://socketo.me/docs/sessions), you must use a session handler other than the native one,
such as [Symfony PDO Session Handler](http://symfony.com/doc/master/cookbook/configuration/pdo_session_storage.html).

Create the following services:

```yml
services:
    pdo:
        class: PDO
        arguments:
            dsn: mysql:host=%database_host%;port=%database_port%;dbname=%database_name%
            user: %database_user%
            password: %database_password%
        calls:
            - [ setAttribute, [3, 2] ] # \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION
    
    session.handler.pdo:
        class:     Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
        arguments: [@pdo, {lock_mode: 0}]
```

[Create table in your DB](http://symfony.com/doc/current/cookbook/configuration/pdo_session_storage.html#mysql)

Configure the Session Handler in your config.yml

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

For example, you want store your client through redis server like previous example. But I want use Predis client instead of native redis client. We will use [SncRedisBundle](https://github.com/snc/SncRedisBundle) to provide my predis client.

Configure my predis client through SncRedisBundle

```yaml
snc_redis:
    clients:
        ws_client:
            type: predis
            alias: client_storage.driver #snc_redis.client_storage.driver
            dsn: redis://127.0.0.1/2
            logging: %kernel.debug%
            options:
                profile: 2.2
                connection_timeout: 10
                read_write_timeout: 30

gos_web_socket:
    client:
		...
        storage:
            driver: @gos_web_socket.client_storage.driver.predis
		...
```

The PHP class :

```php
<?php

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

use Predis\Client;

class PredisDriver implements DriverInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    public function fetch($id)
    {
        $result = $this->client->get($id);
        if (null === $result) {
            return false;
        }

        return $result;
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function contains($id)
    {
        return $this->client->exists($id);
    }

    /**
     * @param string $id
     * @param mixed  $data
     * @param int    $lifeTime
     *
     * @return bool True if saved, false otherwise
     */
    public function save($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            $response = $this->client->setex($id, $lifeTime, $data);
        } else {
            $response = $this->client->set($id, $data);
        }

        return $response === true || $response == 'OK';
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete($id)
    {
        return $this->client->del($id) > 0;
    }
}
```

The service definition :

```yaml
services:
    gos_web_scocket.client_storage.driver.predis:
        class: Gos\Bundle\WebSocketBundle\Client\Driver\PredisDriver
        arguments:
            - @snc_redis.cache
```

**NOTE :** Predis driver class is included in GosWebSocketBundle, just register the service like below to use it.

#Retrieve current user

Whenever `ConnectionInterface` instance is available your are able to retrieve the associated user.

For example inside a topic : 

```php
use Ratchet\ConnectionInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

class AcmeTopic implements TopicInterface
{
    protected $clientStorage;
    
    /**
     * @param ClientStorageInterface $clientStorage
     */
    public function __construct(ClientStorageInterface $clientStorage)
    {
        $this->clientStorage = $clientStorage;
    }

    /**
     * @param ConnectionInterface $connection
     * @param Topic               $topic
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        $currentUser = $this->clientStorage->getClient($this->clientStorage->getStorageId($connection)); //give UserInterface
    }
}
```

Use `Gos\Bundle\WebSocketBundle\Client\WebSocketUserTrait`

```php
use Ratchet\ConnectionInterface;
use Gos\Bundle\WebSocketBundle\Client\WebSocketUserTrait;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

class AcmeTopic implements TopicInterface
{
    use WebSocketUserTrait;
    
    /**
     * @param ClientStorageInterface $clientStorage
     */
    public function __construct(ClientStorageInterface $clientStorage)
    {
        $this->clientStorage = $clientStorage;
    }

    /**
     * @param ConnectionInterface $connection
     * @param Topic               $topic
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        $currentUser = $this->getCurrentUser($connection); //give UserInterface
    }
}
```

## Send a message to a specific user

```php
use Ratchet\ConnectionInterface;
use Gos\Bundle\WebSocketBundle\Client\WebSocketUserTrait;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\Wamp\Topic;

class AcmeTopic implements TopicInterface
{
    use WebSocketUserTrait;
    
    /**
     * @param ClientStorageInterface $clientStorage
     */
    public function __construct(ClientStorageInterface $clientStorage)
    {
        $this->clientStorage = $clientStorage;
    }

    /**
     * @param ConnectionInterface $connection
     * @param Topic               $topic
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        foreach($topic as $subscriber){
            if(false !== $this->getCurrentUser($subscriber) && 'user2' === $this->getCurrentUser($subscriber)->getUsername){
                //$subscriber is our targeted user
                $topic->broadcast('message', array(), array($subscriber->WAMP->sessionId));
            }
        }
    }
}
```

For information on sharing the config between server and client, read the [Sharing Config](code/SharingConfig.md) Code Cookbook.

_For info on bootstrapping the session with extra information, check out the [Events](Events.md)_
