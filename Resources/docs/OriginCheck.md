# Origin Check

## Con

```yaml
gos_web_socket:
	...
    server:
		...
        origin_check: true
    origins:
        - www.mydomain.tld
        - mydomain.tld
```

With this configuration, only connection from `www.mydomain.tld` will be accepted, others will be rejected (and dispatch ClientRejectedEvent, see [Events]('Events.md') for more informations).

## Note
- Note : 403 HTTP Response is send to close connection is this case.
- `localhost` and `127.0.0.1` are automatically added on trusted origins.
