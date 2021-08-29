# Configuration Reference

```yaml
gos_web_socket:
  authentication:
    providers:
      session:

        # The service ID of the session handler service used to read session data.
        session_handler:      null

        # The firewalls from which the session token can be used; can be an array, a string, or null to allow all firewalls.
        firewalls:            null
    storage:

      # The type of storage for the websocket server authentication tokens.
      type:                 in_memory # One of "in_memory"; "psr_cache"; "service", Required

      # The cache pool to use when using the PSR cache storage.
      pool:                 null

      # The service ID to use when using the service storage.
      id:                   null

    # Enables the new authenticator API.
    enable_authenticator: false
  client:               # Deprecated (Since gos/web-socket-bundle 3.11: The child node "client" at path "gos_web_socket" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the new websocket authentication API instead.)

    # The service ID of the session handler service used to read session data.
    session_handler:      ~ # Deprecated (Since gos/web-socket-bundle 3.11: The child node "session_handler" at path "gos_web_socket.client" is deprecated and will be removed in GosWebSocketBundle 4.0. Set the session handler on the session authentication provider instead.)

    # The name of the security firewall to load the authenticated user data for.
    firewall:             ws_firewall # Deprecated (Since gos/web-socket-bundle 3.11: The child node "firewall" at path "gos_web_socket.client" is deprecated and will be removed in GosWebSocketBundle 4.0. Set the firewalls on the session authentication provider instead.)
    storage:              # Deprecated (Since gos/web-socket-bundle 3.11: The child node "storage" at path "gos_web_socket.client" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the "gos_web_socket.authentication.storage" node instead.)

      # The service ID of the storage driver to use for storing connection data.
      driver:               gos_web_socket.client.driver.in_memory # Deprecated (Since gos/web-socket-bundle 3.11: The child node "driver" at path "gos_web_socket.client.storage" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the "gos_web_socket.authentication.storage" node instead.)

      # The cache TTL (in seconds) for clients in storage.
      ttl:                  900 # Deprecated (Since gos/web-socket-bundle 3.11: The child node "ttl" at path "gos_web_socket.client.storage" is deprecated and will be removed in GosWebSocketBundle 4.0. Configure the TTL on the authentication storage driver instead.)

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

  # A list of origins allowed to connect to the websocket server, must match the value from the "Origin" header of the HTTP request.
  origins:              []

  # A list of IP addresses which are not allowed to connect to the websocket server.
  blocked_ip_addresses: []
```
