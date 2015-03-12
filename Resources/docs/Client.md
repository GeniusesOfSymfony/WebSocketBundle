### Autobahn JS with gos_web_socket_client.js

As seen from the example above, gos_web_socket_client.js is a wrapper around Autobahn.js which allows for remote procedure calls and pub sub behaviour.


To make a **Remote Procedure Call (RPC)** from the client you can do the following:

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

**For more information on setting up server side half of RPC, please see [Setting up RPC's](RPCSetup.md)**