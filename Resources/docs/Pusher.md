# Pusher

By different nature of pusher you may need additional php extension, if required it will be mentioned.
 
## ZMQ Pusher

It required **php-zmq** extension. 

**1. PHP extension installation** 

```cmd
sudo pecl install zmq-beta
```

```cmd
touch /etc/php5/mods-available/zmq.ini
echo 'extension=zmq.so' >> /etc/php5/mods-available/zmq.ini
sudo php5enmod zmq
```

You will need to install a new dependency through composer:

```cmd
composer require react/zmq
```

Then reload php-fpm server or apache/nginx if you are not using php-fpm

**2. Bundle Configuration**

```yaml
# Gos Web Socket Bundle
gos_web_socket:
    pushers:
        zmq:
            default: true
            host: 127.0.0.1
            port: 5555
            persistent: true
            protocol: tcp
```

**NOTE :** if `default` set to true service is available through 'gos_web_socket.pusher' insteadof 'gos_web_socket.zmq.pusher'

After that, you will see this message when you start the websocket server

```text
[2015-08-02 17:36:38] websocket.INFO: ZMQ transport listening on 127.0.0.1:5555
```

**3. Push**

```php
$pusher = $this->container->get('gos_web_socket.zmq.pusher');
//push(data, route_name, route_arguments)
$pusher->push(['my_data' => 'data'], 'user_notification', ['username' => 'user1']);
```

## AMQP Pusher

It required **amqp** extension. 

**1. PHP extension installation** 

```cmd
sudo pecl install amqp
```

```cmd
touch /etc/php5/mods-available/amqp.ini
echo 'extension=amqp.so' >> /etc/php5/mods-available/amqp.ini
sudo php5enmod amqp
```

You will need to install a new dependency through composer:

```cmd
composer require gos/react-amqp
```

**2. Bundle Configuration**

```yml
gos_web_socket:
    pushers:
        amqp:
            host: 127.0.0.1
            port: 5672
            login: guest
            password: guest
            vhost: '/'
```

**NOTE** : We create Exchange (`gos_websocket_exchange`) and Queue (`gos_websocket`) **BUT** you need to manually bind the `gos_websocket` queue to `gos_websocket_exchange` from server admin panel.

**3. Push**

```php
$pusher = $this->container->get('gos_web_socket.amqp.pusher');
//push(data, route_name, route_arguments, $context)
$pusher->push(['my_data' => 'data'], 'user_notification', ['username' => 'user1', $context]);
```

**NOTE :** `$context` is optional but you can pass option to publish method like 'routing_key', 'publish_flags' and 'attributes' (**See** : https://github.com/pdezwart/php-amqp/blob/master/amqp_exchange.c#L576) 
## Websocket Pusher

**NOTE :** Websocket Pusher is not the most faster and powerful because he have a lot of overhead (Handshake have high cost).

**NOTE 2 :** He call directly `onPublish` method not `onPush` because we use WAMP protocol.

**1. Bundle Configuration**

```yml
gos_web_socket:
    pushers:
        wamp:
            host: 127.0.0.1
            port: 1337
```

**2. Push**

```php
$pusher = $this->container->get('gos_web_socket.wamp.pusher');
//push(data, route_name, route_arguments)
$pusher->push(['my_data' => 'data'], 'user_notification', ['username' => 'user1']);
```

## Rely push to your topic

Implement `PushableTopicInterface` interface to your topic 
```php
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\PushableTopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

class AcmeTopic implements TopicInterface, PushableTopicInterface
{
    /**
     * @param Topic        $topic
     * @param WampRequest  $request
     * @param array|string $data
     * @param string       $provider The name of pusher who push the data
     */
    public function onPush(Topic $topic, WampRequest $request, $data, $provider)
    {
        $topic->broadcast($data);
    }
}
```

# Pusher event

When pusher send message or fail to send it, we dispatch event to allow you to plug your own logic.

- **Success** : `gos_web_socket.push_success`
- **Fail** : `gos_web_socket.push_fail`

Will give an `Gos\Bundle\WebSocketBundle\Event\PushHandlerEvent` where can access to the message and to the handler.

# Last word

**You can use pushers all together !**

**Config**

```yml
    pushers:
        zmq:
            default: true
            host: 127.0.0.1
            port: 5555
            persistent: true
            protocol: tcp
        amqp:
            host: 127.0.0.1
            port: 5672
            login: guest
            password: guest
```

```php
$pusher = $this->container->get('gos_web_socket.amqp.pusher');
$pusher->push($message, 'user_notification', ['username' => 'user1']);

$pusher = $this->container->get('gos_web_socket.zmq.pusher');
$pusher->push($message, 'user_notification', ['username' => 'user1']);
```
