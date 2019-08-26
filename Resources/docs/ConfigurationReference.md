# Configuration Reference

```yaml
gos_web_socket:
    client:
        session_handler:      ~ # Example: @session.handler.pdo
        firewall:             ws_firewall # Example: secured_area
        storage:
            driver:               'gos_web_socket.client.driver.in_memory' # Example: gos_web_socket.client.driver.in_memory
            ttl:                  900 # Example: 3600
            prefix:               '' # Example: client
            decorator:            ~
    shared_config:        true
    server:
        port:                 ~ # Required, Example: 1337
        host:                 ~ # Required, Example: 127.0.0.1
        origin_check:         false # Example: true

        # Flag indicating a keepalive ping should be enabled on the server
        keepalive_ping:       false # Example: true

        # The time in seconds between each keepalive ping
        keepalive_interval:   30 # Example: 30
        router:
            resources:            []
    origins:              []
    ping:
        services:

            # Prototype
            -

                # The name of the service to ping
                name:                 ~ # Required

                # The type of the service to be pinged; valid options are "doctrine" and "pdo"
                type:                 ~ # One of "doctrine"; "pdo", Required
    pushers:
        amqp:
            enabled:              false
            host:                 ~ # Required, Example: 127.0.0.1
            port:                 ~ # Required, Example: 5672
            login:                ~ # Required
            password:             ~ # Required
            vhost:                /
            read_timeout:         0
            write_timeout:        0
            connect_timeout:      0
            queue_name:           gos_websocket
            exchange_name:        gos_websocket_exchange
        wamp:
            enabled:              false
            host:                 ~ # Required, Example: 127.0.0.1
            port:                 ~ # Required, Example: 1337
            ssl:                  false
            origin:               null
```
