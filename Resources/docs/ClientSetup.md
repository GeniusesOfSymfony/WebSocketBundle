# Client Setup

## Step 1: Include JavaScript
To include the relevant JavaScript libraries necessary for GosWebSocketBundle, add these to your root layout file just before the closing body tag.

```twig
<script src="{{ asset('bundles/goswebsocket/js/vendor/autobahn.min.js') }}"></script>
<script src="{{ asset('bundles/goswebsocket/js/websocket.min.js') }}"></script>
```

_Note: This requires Twig and the FrameworkBundle to be installed and the `assets:install` command to be run_

```sh
php bin/console assets:install
```

If you are NOT using Twig as a templating engine, you will need to include the following JavaScript files from the bundle:

```javascript
GosWebSocketBundle/Resources/public/js/vendor/autobahn.min.js
GosWebSocketBundle/Resources/public/js/websocket.min.js
```

## Step 2: websocket.js

Once the JavaScript is included, you can start using websocket.js to interact with the web socket server. If you want to avoid hardcoding the connection URI here, see the code tip on [sharing the config](code/SharingConfig.md)

A `WS` object is made available in the global scope of the page. This can be used to connect to the server as follows:

```javascript
var websocket = WS.connect("ws://127.0.0.1:8080");
```

The following commands are available to a GosSocket object returned by WS.connect.

### GosSocket.on(event, callback)

This allows you to listen for events called by GosSocket. The only events fired currently are "socket/connect" and "socket/disconnect".

```javascript
var webSocket = WS.connect("ws://127.0.0.1:8080");

webSocket.on("socket/connect", function (session) {
    //session is an Autobahn JS WAMP session.

    console.log("Successfully Connected!");
})

webSocket.on("socket/disconnect", function (error) {
    //error provides us with some insight into the disconnection: error.reason and error.code

    console.log("Disconnected for " + error.reason + " with code " + error.code);
})
```

Clients subscribe to "Topics", Clients publish to those same topics. When this occurs, anyone subscribed will be notified.

For a more in depth description of PubSub architecture, see [Autobahn JS PubSub Documentation](http://autobahn.ws/js/reference_wampv1.html)

* `session.subscribe(topic, function(uri, payload))`
* `session.unsubscribe(topic)`
* `session.publish(topic, event, exclude, eligible)`

These are all fairly straightforward, here's an example on using them:

```javascript
webSocket.on("socket/connect", function (session) {

    //the callback function in "subscribe" is called everytime an event is published in that channel.
    session.subscribe("acme/channel", function (uri, payload) {
        console.log("Received message", payload.msg);
    });

    session.publish("acme/channel", "This is a message!");
})
```

**Next step :** Before being able to subscribe/publish/unsubscribe, you need to setup a [Topic Handler](TopicSetup.md). In the next step you will find an example Topic Handler that you can use to test the bundle PubSub functionality. If your application requires more complexity than just repeating messages in channels, you can freely customize the Topic Handler Class.

For more information on using the WAMP Session objects, please refer to the [official autobahn documentation](http://autobahn.ws/js)

