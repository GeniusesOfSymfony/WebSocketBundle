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
  server:

    # The host IP address on the server which connections for the websocket server are accepted.
    host:                 ~ # Required

    # The port on the server which connections for the websocket server are accepted.
    port:                 ~ # Required

    tls:

      # Enables the native tls support that can be configured with the options below.
      enabled: false

      # The options to set up the tls configuration. See the example below or see https://www.php.net/manual/en/context.ssl.php for all available options.
      options:
        local_cert: '/path/to/cert/cert.crt'
        local_pk: '/path/to/key/mykey.key'
        verify_peer: false

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
