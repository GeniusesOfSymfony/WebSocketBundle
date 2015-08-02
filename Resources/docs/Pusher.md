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

Then reload php-fpm server or apache/nginx if you are not using php-fpm

**2. Bundle Configuration**

```yaml
# Gos Web Socket Bundle
gos_web_socket:
    pusher:
        type: zmq
        host: 127.0.0.1
        port: 5555
        options:
            persistent: true
            protocol: tcp
```

**3. Push**

```php
$pusher = $this->container->get('gos_web_socket.pusher');
//push(data, route_name, route_arguments)
$pusher->push(['my_data' => 'data'], 'user_notification', ['username' => 'user1']);
```

## AMQ Pusher

## Websocket Pusher

**NOTE :** Websocket Pusher is not the most faster and powerfull because he have a lot of overhead.


