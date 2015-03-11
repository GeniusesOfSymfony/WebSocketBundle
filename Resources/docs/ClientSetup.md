#Client Setup

###Step 1: Include Javascript
To include the relevant javascript libraries necessary for Gos WebSocket, add these to your root layout file just before the closing body tag.

```twig
...

{{ ws_client() }}
</body>
```
_Note: This requires assetic and twig to be installed_

If you are NOT using twig as a templating engine, you will need to include the following javascript files from the bundle:

```javascript
GosWebSocketBundle/Resources/public/js/vendor/autobahn.min.js
GosWebSocketBundle/Resources/public/js/gos_web_socket_client.js
```

If using the {{ ws_client() }} method, please ensure to run the following when using the production environment:

```command
php app/console assetic:dump --env=prod --no-debug
```

This is to ensure the client js libraries are available.


###Step 2: gos_web_socket_client.js

Once the javascript is included, you can start using gos_web_socket_client.js to interact with the web socket server.

A "WS" object is made available in the global scope of the page. This can be used to connect to the server as follows:

```javascript
var websocket = WS.connect("ws://localhost:8080");
```

The following commands are available to a GosSocket object returned by WS.connect.

#### GosSocket.on(event, callback)

This allows you to listen for events called by GosSocket. The only events fired currently are "socket/connect" and "socket/disconnect".

```javascript
var myWebSocket = WS.connect("ws://localhost:8080");

myWebSocket.on("socket/connect", function(session){
    //session is an Autobahn JS WAMP session.

    console.log("Successfully Connected!");
})

myWebSocket.on("socket/disconnect", function(error){
    //error provides us with some insight into the disconnection: error.reason and error.code

    console.log("Disconnected for " + error.reason + " with code " + error.code);
})
```

### Step 3: Autobahn JS with gos_web_socket_client.js

As seen from the example above, gos_web_socket_client.js is a wrapper around Autobahn.js which allows for remote procedure calls and pub sub behaviour.

If you want to avoid hardcoding the connection URI here, see the code tip on [sharing the config](code/SharingConfig.md)

To make a Remote Procedure Call (RPC) from the client you can do the following:

#### session.call(commandName, parameters)

```javascript

myWebSocket.on("socket/connect", function(session){

    //this will call the server side function "Sample::addFunc"
    session.call("sample/add_func", [2, 5])
        .then(  //using "then" promises.

            function(result) //the function for a valid result
            {
                console.log("RPC Valid!", result);
            },

            function(error, desc) // the function to handle an error
            {
                console.log("RPC Error", error, desc);
            }

        );
})

```

_For more information on setting up server side half of RPC, please see [Setting up RPC's](RPCSetup.md)_


The other way to interact with the server is the popular "Pub/Sub" method. This is essentially:

Clients subscribe to "Topics", Clients publish to those same topics. When this occurs, anyone subscribed will be notified.

For a more in depth description of PubSub architecture, see [Autobahn JS PubSub Documentation](http://autobahn.ws/js/reference_wampv1.html)

#### session.subscribe(topic, function(uri, payload))
#### session.unsubscribe(topic)
#### session.publish(topic, payload)

These are all fairly straightforward, here's an example on using them:

```javascript
myWebSocket.on("socket/connect", function(session){

    //the callback function in "subscribe" is called everytime an event is published in that channel.
    session.subscribe("acme/channel", function(uri, payload){
        console.log("Received message", payload.msg);
    });

    session.publish("acme/channel", {msg: "This is a message!"});

    session.unsubscribe("acme/channel");

    session.publish("acme/channel", {msg: "I won't see this"});
})
```

If your application requires more complexity than just repeating messages in channels, please see [custom server side topic handlers](TopicSetup.md)

___
For more information on using the WAMP Session objects, please refer to the [official autobahn documentation](http://autobahn.ws/js)

