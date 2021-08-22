# Configuration Reference

```yaml
gos_web_socket:
  authentication:
    providers:
      session:

        # The firewalls from which the session token can be used; can be an array, a string, or null to allow all firewalls.
        firewalls:            null
    storage:

      # The type of storage for the websocket server authentication tokens.
      type:                 in_memory # One of "in_memory"; "psr_cache"; "service", Required

      # The cache pool to use when using the PSR cache storage.
      pool:                 null

      # The service ID to use when using the service storage.
      id:                   null
  client:

    # The service ID of the session handler service used to read session data.
    session_handler:      ~

    # The name of the security firewall to load the authenticated user data for.
    firewall:             ws_firewall # Deprecated (Since gos/web-socket-bundle 3.11: The child node "firewall" at path "gos_web_socket.client" is deprecated and will be removed in GosWebSocketBundle 4.0. Set the firewalls on the session authentication provider instead.)
    storage:              # Deprecated (Since gos/web-socket-bundle 3.11: The child node "storage" at path "gos_web_socket.client" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the "gos_web_socket.authentication.storage" node instead.)

      # The service ID of the storage driver to use for storing connection data.
      driver:               gos_web_socket.client.driver.in_memory # Deprecated (Since gos/web-socket-bundle 3.11: The child node "driver" at path "gos_web_socket.client.storage" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the "gos_web_socket.authentication.storage" node instead.)

      # The cache TTL (in seconds) for clients in storage.
      ttl:                  900 # Deprecated (Since gos/web-socket-bundle 3.11: The child node "ttl" at path "gos_web_socket.client.storage" is deprecated and will be removed in GosWebSocketBundle 4.0. Configure the TTL on the authentication storage driver instead.)
      prefix:               '' # Deprecated (Since gos/web-socket-bundle 3.1: The child node "prefix" at path "gos_web_socket.client.storage" is deprecated and will be removed in GosWebSocketBundle 4.0.)

      # The service ID of a decorator for the client storage driver.
      decorator:            ~ # Deprecated (Since gos/web-socket-bundle 3.11: The child node "decorator" at path "gos_web_socket.client.storage" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the "gos_web_socket.authentication.storage" node instead.)
  server:

    # The host IP address on the server which connections for the websocket server are accepted.
    host:                 ~ # Required

    # The port on the server which connections for the websocket server are accepted.
    port:                 ~ # Required

    # Enables checking the Origin header of websocket connections for allowed values.
    origin_check:         false

    # Enables checking the originating IP address of websocket connections for blocked addresses.
    ip_address_check:     false

    # Flag indicating a keepalive ping should be enabled on the server.
    keepalive_ping:       false

    # The time in seconds between each keepalive ping.
    keepalive_interval:   30
    router:
      resources:

        # Prototype
        -
          resource:             ~ # Required
          type:                 null # One of "closure"; "container"; "glob"; "php"; "xml"; "yaml"; null
  ping:
    services:

      # Prototype
      -

        # The name of the service to ping.
        name:                 ~ # Required

        # The type of the service to be pinged.
        type:                 ~ # One of "doctrine"; "pdo", Required

        # The time (in seconds) between executions of this ping.
        interval:             20
  pushers:              # Deprecated (Since gos/web-socket-bundle 3.1: The child node "pushers" at path "gos_web_socket" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the symfony/messenger component instead.)
    amqp:                 # Deprecated (Since gos/web-socket-bundle 3.1: The child node "amqp" at path "gos_web_socket.pushers" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the symfony/messenger component instead.)
      enabled:              false
      host:                 ~ # Required
      port:                 ~ # Required
      login:                ~ # Required
      password:             ~ # Required
      vhost:                /
      read_timeout:         0
      write_timeout:        0
      connect_timeout:      0
      queue_name:           gos_websocket
      exchange_name:        gos_websocket_exchange
    wamp:                 # Deprecated (Since gos/web-socket-bundle 3.1: The child node "wamp" at path "gos_web_socket.pushers" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the symfony/messenger component instead.)
      enabled:              false
      host:                 ~ # Required
      port:                 ~ # Required
      ssl:                  false
      origin:               null
  websocket_client:     # Deprecated (Since gos/web-socket-bundle 3.4: The child node "websocket_client" at path "gos_web_socket" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the ratchet/pawl package instead.)
    enabled:              false
    host:                 ~ # Required
    port:                 ~ # Required
    ssl:                  false
    origin:               null
  shared_config:        true # Deprecated (Since gos/web-socket-bundle 3.9: The child node "shared_config" at path "gos_web_socket" is deprecated and will be removed in GosWebSocketBundle 4.0.)

  # A list of origins allowed to connect to the websocket server, must match the value from the "Origin" header of the HTTP request.
  origins:              []

  # A list of IP addresses which are not allowed to connect to the websocket server.
  blocked_ip_addresses: []
```
