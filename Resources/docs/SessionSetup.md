#Session sharing

Thanks to Ratchet its easy to get the shared info from the same website session. As per the [Ratchet documentation](http://socketo.me/docs/sessions), you must use a session handler other than the native one, such as [Symfony PDO Session Handler](http://symfony.com/doc/master/cookbook/configuration/pdo_session_storage.html).

Once this is setup you will have something similar to the following in your config.yml

```yaml
framework:
    ...
    session:
        handler_id: session.handler.pdo
```

This is what informs Symfony2 of what to use as the session handler.

Similarly, we can do the same thing with Gos WebSocket to enable a shared session.


```yaml
gos_web_socket:
    ...
    session_handler: @session.handler.pdo
```

By using the same value, we are using the same exact service in both the WebSocket server and the Symfony2 application.

If you experience any issues with the session being empty or not as expected. Please ensure that everything is connecting via the same URL, so the cookies can be read.

For information on sharing the config between server and client, read the [Sharing Config](code/SharingConfig.md) Code Cookbook.

_For info on bootstrapping the session with extra information, check out the [Events](Events.md)_
