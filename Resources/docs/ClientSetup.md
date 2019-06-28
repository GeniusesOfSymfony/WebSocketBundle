# Client Setup

To set up the client to connect to your server, there are two options readily available.

## Option A: Include Bundle JavaScript

This is the simplest option and allows you to include the JavaScript files provided by the bundle.

### Step 1: Include JavaScript in template

You will need to include two JavaScript files to make the connection work; the AutobahnJS library and the bundle's client script. It is recommended these be added at the end of your template before the closing body tag, but before any other application scripts that may need them.
To include the relevant JavaScript libraries necessary for GosWebSocketBundle, add these to your root layout file just before the closing body tag.

```twig
<script src="{{ asset('bundles/goswebsocket/js/vendor/autobahn.min.js') }}"></script>
<script src="{{ asset('bundles/goswebsocket/js/websocket.min.js') }}"></script>
```

_Note: This requires the FrameworkBundle and TwigBundle to be active in your application and the `assets:install` command to be run_

If you are NOT using Twig as a templating engine, you will need to include the following JavaScript files from the bundle:

- GosWebSocketBundle/Resources/public/js/vendor/autobahn.min.js
- GosWebSocketBundle/Resources/public/js/websocket.min.js

### Step 2: Initialize the connection

Once the JavaScript is included, you can start using websocket.js to interact with the websocket server. If you want to avoid hardcoding the connection URI here, see the code tip on [sharing the config](code/SharingConfig.md)

A `WS` object is made available in the global scope of the page. This can be used to connect to the server as follows:

```javascript
var websocket = WS.connect("ws://127.0.0.1:8080");
```

## Option B: Include in your application's JavaScript files

If you are using Webpack and [Encore](https://symfony.com/doc/current/frontend.html) to manage your site's assets, you can include the bundle's script file in your own application's scripts.

```javascript
import WS from '../../vendor/gos/web-socket-bundle/Resources/public/js/websocket.min.js';
```

# JavaScript API

The following describes the API made available through the JavaScript client.

## WS object

The `WS` object is a global object that allows you to create new connections to your websocket server.

### WS.connect(uri, sessionConfig = null)

The `WS.connect()` function creates a new `GosSocket` object and connects to the websocket server.

The `uri` parameter is the URI for your websocket server (i.e. `"ws://127.0.0.1:8080"`).

The `sessionConfig` parameter optionally allows you to customize the options in the AutobahnJS API, you must pass an object containing any of the following keys to customize these values:

- retryDelay: Number, the delay (in milliseconds) between retry attempts when the connection is lost (defaults to 5000)
- maxRetries: Number, the maximum number of retry attempts when the connection is lost (defaults to 10)
- skipSubprotocolCheck: Boolean, unknown use
- skipSubprotocolAnnounce: Boolean, unknown use

## GosSocket object

The `GosSocket` object is a wrapper around the AutobahnJS API and provides helpers for creating a connection and triggering events

### Public Methods

### GosSocket.on(event, listener)

The `GosSocket.on()` function lets you add a callback for an event.

The `event` parameter is the name of the event to subscribe to. Currently, the bundle only uses `socket/connect` and `socket/disconnect`.

The `listener` parameter is a callback function to be triggered when the event is fired off. When executed, `this` is scoped to the `GosSocket` instance firing the event. Callbacks receive one parameter, a `data` parameter of any type. Please see the examples below for more details.

### GosSocket.off(event, listener)

The `GosSocket.off()` function lets you remove a callback for an event.

The `event` parameter is the name of the event to subscribe to. Currently, the bundle only uses `socket/connect` and `socket/disconnect`.

The `listener` parameter is a callback function to be removed from the event

The following commands are available to a GosSocket object returned by WS.connect.

### Private Methods

Although JavaScript does not natively support the notion of public or private functions, the below functions are considered private to the `GosSocket` object and not intended for public use.

### GosSocket._connect(uri, sessionConfig = null)

The `GosSocket._connect()` function is a wrapper around AutobahnJS' `ab.connect()` function and handles firing the `socket/connect` and `socket/disconnect` events.

### GosSocket._fire(event, data = null)

The `GosSocket._fire()` function handles calling all listeners for an event.

## Examples

### Basic Usage

```javascript
var webSocket = WS.connect("ws://127.0.0.1:8080");

webSocket.on("socket/connect", function (session) {
    //session is an AutobahnJS WAMP session.

    console.log("Successfully Connected!");
})

webSocket.on("socket/disconnect", function (error) {
    //error provides us with some insight into the disconnection: error.reason and error.code

    console.log("Disconnected for " + error.reason + " with code " + error.code);
})
```

### `this` Scope

As mentioned before, `this` is scoped to the `GosSocket` object when calling listeners.

```javascript
class MySocket {
    constructor() {
        this._someValue = 42;
        this._webSocket = null;
        this._webSocketSession = null;
    }

    connect(uri) {
        let _this = this;

        this._webSocket = WS.connect(uri);

        this._webSocket.on("socket/connect", function (session) {
            // this is the GosSocket object
            this.on("socket/disconnect", function (error) {});
            
            // To set a class property, need to use the `_this` var we declared before
            _this._webSocketSession = session;
        });

        // Or, you can use arrow functions to have `this` scoped correctly
        this.on("socket/disconnect", (error) => {
            this._webSocketSession = null;
        });
    }
}
```

### In-Depth Example

Clients subscribe to "Topics", Clients publish to those same topics. When this occurs, anyone subscribed will be notified.

For a more in depth description of PubSub architecture, see [AutobahnJS PubSub Documentation](http://autobahn.ws/js/reference_wampv1.html)

* `session.subscribe(topic, function(uri, payload))`
* `session.unsubscribe(topic)`
* `session.publish(topic, event, exclude, eligible)`

These are all fairly straightforward, here's an example on using them:

```javascript
var webSocket = WS.connect("ws://127.0.0.1:8080");

webSocket.on("socket/connect", function (session) {

    //the callback function in "subscribe" is called everytime an event is published in that channel.
    session.subscribe("acme/channel", function (uri, payload) {
        console.log("Received message", payload.msg);
    });

    session.publish("acme/channel", "This is a message!");
})
```

**Next step :** Before being able to subscribe/publish/unsubscribe, you need to setup a [Topic Handler](TopicSetup.md). In the next step you will find an example Topic Handler that you can use to test the bundle PubSub functionality. If your application requires more complexity than just repeating messages in channels, you can freely customize the Topic Handler Class.

For more information on using the WAMP Session objects, please refer to the [official AutobahnJS documentation](http://autobahn.ws/js)

