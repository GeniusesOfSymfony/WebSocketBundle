# Configuration Reference

```yaml
gos_web_socket:
    client:
        session_handler: ~ # Example: @session.handler.pdo
        firewall: ws_firewall # Example: secured_area, you must replace it by your firewall
        storage:
            driver: @Gos\Bundle\WebSocketBundle\Client\Driver\InMemoryDriver
            decorator: ~
    shared_config: true
    assetic: true #use assetic bundle
    server:
        port: ~ # Required, Example 1337
        host: ~ # Required, Example 127.0.0.1
        origin_check:         false
        router:
            resources:
                - @AcmeBundle/Resources/config/pubsub/routing.yml
            context:
                tokenSeparator: "/"
    rpc:                  []
    topics:               []
    periodic:             []
    servers:              []
    origins:              []
```
