#Share config between server and client

Its useful to keep the server configuration isolated from the application. Here is a trivial way to share the Gos WebSocket Config between the server and the client.

###Step 1: Enable the shared_config in "app/config/config.yml"

```yaml
gos_web_socket:
    shared_config: true
```

###Step 2: Render in template

In your root twig layout template, add the following

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
