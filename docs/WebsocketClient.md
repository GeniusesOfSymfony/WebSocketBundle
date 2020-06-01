# Websocket Client

The GosWebSocketBundle includes the `gos/websocket-client` package which provides a PHP client for communicating with a websocket server.

## Configuration

To use the websocket client, you will need to enable it in the bundle's configuration. For an application based on the Symfony Standard structure, you will need to update your `app/config/config.yml` file. For an application based on Symfony Flex, use the `config/packages/gos_web_socket.yaml` file.

```yaml
gos_web_socket:
    websocket_client:
        enabled: true # Flag to enable the client
        host: 127.0.0.1 # This will probably be the same as your `gos_web_socket.server.host` value
        port: 80 # This will probably be the same as your `gos_web_socket.server.port` value
        ssl: false # Flag to enable SSL connections to the websocket server, default false
        origin: null # The origin domain for the client, default null (if origin checking is enabled on your websocket server, this value must be allowed)
```

## Using the Client

Using dependency injection, you can inject a `Gos\Component\WebSocketClient\Wamp\ClientInterface` instance into your class, which will have your configured websocket client ready for use. You can then interact with your websocket server.

The below example is based on the deprecated WAMP pusher:

```php
<?php

use Gos\Component\WebSocketClient\Wamp\ClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class PostController extends AbstractController
{
    public function update(Request $request, ClientInterface $websocketClient)
    {
        // Do stuff...

        $websocketClient->connect();
        $websocketClient->publish('/topic', json_encode([]));
        $websocketClient->disconnect();
    }
}
```
