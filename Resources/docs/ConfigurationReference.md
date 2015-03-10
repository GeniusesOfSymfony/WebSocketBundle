# Configuration Reference

```yaml
gos_web_socket:
    client:
        firewall: secured_area #or array of firewall
        session_handler: @session.handler.pdo
        storage:
            driver: @gos_web_scocket.client_storage.driver.predis
            decorator: @gos_web_socket.client_storage.doctrine.decorator
    shared_config: true
    server:
        host: websocket.dev
        port: 1337
        origin_check: true
    origins:
        - websocket.dev
        - www.websocket.dev
```