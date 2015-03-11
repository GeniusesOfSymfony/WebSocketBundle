#Share config between server and client

Its useful to keep the server configuration isolated from the application. Here is a trivial way to share the Gos WebSocket Config between the server and the client.

By default the config is shared, you can turn it off if you don't need in `app/config/config.yml`

```yaml
gos_web_socket:
    shared_config: false
```

## How Access to the shared config

###Twig
```html
<script type="text/javascript">
    var _WS_URI = "ws://{{ gos_web_socket_server_host }}:{{ gos_web_socket_server_port }}";
</script>
```

Now you will have access to a variable "_WS_URI" which you can connect with:

```javascript
var myWs = WS.connect(_WS_URI);
```

Alternatively, if you don't like polluting your global scope, you can render it directly into your javascript file by processing it via a controller.

###Symfony2 DIC

In service YAML :
```yaml
    %web_socket_server.host% #host
    %web_socket_server.port% #port
```

In php : 
```php
//Host
$container->getParameter('web_socket_server.host');

//Port
$container->getParameter('web_socket_server.port');
```

