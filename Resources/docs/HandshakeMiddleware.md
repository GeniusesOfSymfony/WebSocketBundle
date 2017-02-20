# HandshakeMiddleware

You can add any middleware as service to server with your business logic  
 

**Bundle Configuration**

```yaml
# Gos Web Socket Bundle
gos_web_socket:
    server:
        handshake_middleware: 
            - @some_service # implements Gos\Bundle\WebSocketBundle\Server\App\Stack\HandshakeMiddlewareInterface
```
