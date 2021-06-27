# Securing the Websocket Server

Thanks to the features available in Ratchet, the bundle can be configured to provide extra security checks to block unwelcome traffic by checking the origin or IP address.

Note, it is recommended these types of checks are performed at a higher level in your application stack, such as a reverse proxy or load balancer, but these features are available for ease of use.

## Checking origin addresses

The bundle can be configured to only allow requests when traffic originates from a list of allowed domains. When enabled, `localhost` and `127.0.0.1` are always allowed.

```yaml
# config/packages/gos_web_socket.yaml
gos_web_socket:
    server:
        # Enables checking the Origin header of websocket connections for allowed values.
        origin_check: true

    # A list of origins allowed to connect to the websocket server, must match the value from the "Origin" header of the HTTP request.
    origins:
        - www.example.com
        - example.com
```

With this configuration, only connections from `www.example.com` and `example.com` will be accepted, others will be rejected.

## Checking IP addresses

The bundle can be configured to only allow requests when traffic originates from a list of allowed domains. When enabled, `localhost` and `127.0.0.1` are always allowed.

```yaml
# config/packages/gos_web_socket.yaml
gos_web_socket:
    server:
        # Enables checking the originating IP address of websocket connections for blocked addresses.
        ip_address_check: true

    # A list of origins allowed to connect to the websocket server, must match the value from the "Origin" header of the HTTP request.
    blocked_ip_addresses:
        - 8.8.8.8
        - 192.168.1.1
```

With this configuration, all connections from `8.8.8.8` and `192.168.1.1` will be rejected.

## Handling rejected connections

When a connection is rejected, the `Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent` event is dispatched containing a `Ratchet\ConnectionInterface` instance holding the connection object for the connection being rejected and, optionally, a `Psr\Http\Message\RequestInterface` object (when available in the context the event is dispatched from) representing the HTTP request to the websocket server.
