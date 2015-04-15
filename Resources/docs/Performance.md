Performance
-----------

Benchmarked with [https://github.com/observing/thor](https://github.com/observing/thor).

This benchmark was done on my personal computer without any specific tweak.  

- Linux Cinnamon 17.1 64-bit (Kernel 3.13.0-24 Generic)
- Intel Core i7-2670QM 2.20 Ghz x 4
- 8 Go Ram

#### Websocket Bundle config :

```yaml
gos_web_socket:
    client:
        firewall: secured_area
        session_handler: @session.handler.pdo
        storage:
            driver: @gos_web_socket.client_storage.driver.predis
    shared_config: true
    server:
        host: notification.dev
        port: 1337
        origin_check: false
        router:
            resources:
                - @GosNotificationBundle/Resources/config/pubsub/websocket/notification.yml
                - @GosNotificationBundle/Resources/config/pubsub/websocket/notification_rpc.yml
    origins:
        - www.notification.dev
        - notification.dev

```

#### Thor

```
thor --amount 10000 ws://notification.dev:1337 -C 5000 -W 4 -M 100
```

- Create 5000 concurrent/parallel connections.
- Smash 10000 connections
- Spawn 4 workers.
- Send 100 messages per connection

```text
Online               47030 milliseconds
Time taken           47036 milliseconds
Connected            10000
Disconnected         0
Failed               0
Total transferred    25.11MB
Total received       1.93MB

Durations (ms):
                     min     mean     stddev  median max    
Handshaking          442     17510      7450   22799 23713  
Latency              0       0             1       0 35     

Percentile (ms):
                      50%     66%     75%     80%     90%     95%     98%     98%    100%   
Handshaking          22799   23238   23450   23490   23554   23598   23640   23655   23713  
Latency              0       1       1       1       1       1       1       1       35 

```