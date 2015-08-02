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
    pushers:
        zmq:
            default: true
            host: 127.0.0.1
            port: 5555
            options:
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

## Websocket Pusher

**NOTE :** Websocket Pusher is not the most faster and powerfull because he have a lot of overhead.


