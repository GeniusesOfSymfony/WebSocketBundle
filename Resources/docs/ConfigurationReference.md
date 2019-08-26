# Configuration Reference

```yaml
gos_web_socket:
    client:
        session_handler:      ~ # Example: @session.handler.pdo
        firewall:             ws_firewall # Example: secured_area
        storage:
            driver:               '@gos_web_socket.server.in_memory.client_storage.driver' # Example: @gos_web_socket.server.in_memory.client_storage.driver
            ttl:                  900 # Example: 3600
            prefix:               '' # Example: client
            decorator:            ~
    assetic:              true # Deprecated (The child node "assetic" at path "gos_web_socket" is deprecated.)
    shared_config:        true
    server:
        port:                 ~ # Required, Example: 1337
        host:                 ~ # Required, Example: 127.0.0.1
        origin_check:         false # Example: 1
        router:
            resources:            []
            context:
                tokenSeparator:       / # Example: /
    rpc:                  [] # Deprecated (The child node "rpc" at path "gos_web_socket" is deprecated. Add the `gos_web_socket.rpc` tag to your service definitions instead.)
    topics:               [] # Deprecated (The child node "topics" at path "gos_web_socket" is deprecated. Add the `gos_web_socket.topic` tag to your service definitions instead.)
    periodic:             [] # Deprecated (The child node "periodic" at path "gos_web_socket" is deprecated. Add the `gos_web_socket.periodic` tag to your service definitions instead.)
    servers:              [] # Deprecated (The child node "servers" at path "gos_web_socket" is deprecated. Add the `gos_web_socket.server` tag to your service definitions instead.)
    origins:              []
    pushers:
        zmq:                  # Deprecated (The child node "zmq" at path "gos_web_socket.pushers" is deprecated. Support for ZMQ will be removed.)
            default:              false
            host:                 ~ # Required, Example: 127.0.0.1
            port:                 ~ # Required, Example: 1337
            persistent:           true
            protocol:             tcp # One of "tcp"; "ipc"; "inproc"; "pgm"; "epgm"
            linger:               -1
        amqp:
            default:              false
            host:                 ~ # Required, Example: 127.0.0.1
            port:                 ~ # Required, Example: 1337
            login:                ~ # Required
            password:             ~ # Required
            vhost:                /
            read_timeout:         0
            write_timeout:        0
            connect_timeout:      0
            queue_name:           gos_websocket
            exchange_name:        gos_websocket_exchange
        wamp:
            host:                 ~ # Required, Example: 127.0.0.1
            port:                 ~ # Required, Example: 1337
            ssl:                  false
            origin:               null
```
