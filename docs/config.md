# Configuration Reference

```yaml
gos_web_socket:
  client:

    # The service ID of the session handler service used to read session data.
    session_handler:      ~

    # The name of the security firewall to load the authenticated user data for.
    firewall:             ws_firewall
    storage:

      # The service ID of the storage driver to use for storing connection data.
      driver:               gos_web_socket.client.driver.in_memory

      # The cache TTL (in seconds) for clients in storage.
      ttl:                  900

      # The service ID of a decorator for the client storage driver.
      decorator:            ~
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

  # A list of origins allowed to connect to the websocket server, must match the value from the "Origin" header of the HTTP request.
  origins:              []

  # A list of IP addresses which are not allowed to connect to the websocket server.
  blocked_ip_addresses: []
  ping:
    services:

      # Prototype
      -

        # The name of the service to ping.
        name:                 ~ # Required

        # The type of the service to be pinged.
        type:                 ~ # One of "doctrine"; "pdo", Required
```
