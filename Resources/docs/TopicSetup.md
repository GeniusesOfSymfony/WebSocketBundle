#Topic Handler Setup

Although the standard Gos WebSocket PubSub can be useful as a simple channel for allowing messages to be pushed to users, anymore advanced functionality will require custom Topic Handlers.

Similar to RPC, topic handlers are slightly specialised Symfony2 services. They must implement the interface `Gos\Bundle\WebSocketBundle\Topic\TopicInterface`

### What is a topic ?
A topic is the representation of a pubsub channel. For example you want create a channel for your chat with room.

You will have a prefix `chat` and multiple channel like `all`, `room1`, `room2` that give : 
* `chat/all`
* `chat/room1`
* `chat/room2`
* `chat/*`

You just have to register a topic who catch all channel prefixed by `chat` to handle pubsub. A topic can only support one prefix.


##Step 1: Create the Topic Handler Service Class

```php
<?php

namespace Acme\HelloBundle\Topic;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface as Conn;

class AcmeTopic implements TopicInterface
{

    /**
     * This will receive any Subscription requests for this topic.
     *
     * @param \Ratchet\ConnectionInterface $connection
     * @param $topic
     * @return void
     */
    public function onSubscribe(ConnectionInterface $connection, $topic)
    {
        //this will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast($connection->resourceId . " has joined " . $topic->getId());
    }

    /**
     * This will receive any UnSubscription requests for this topic.
     *
     * @param \Ratchet\ConnectionInterface $connection
     * @param $topic
     * @return void
     */
    public function onUnSubscribe(ConnectionInterface $connection, $topic)
    {
        //this will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast($connection->resourceId . " has left " . $topic->getId());
    }


    /**
     * This will receive any Publish requests for this topic.
     *
     * @param \Ratchet\ConnectionInterface $connection
     * @param $topic
     * @param $event
     * @param array $exclude
     * @param array $eligible
     * @return mixed|void
     */
    public function onPublish(ConnectionInterface $connection, $topic, $event, array $exclude, array $eligible)
    {
        /*
        $topic->getId() will contain the FULL requested uri, so you can proceed based on that

        e.g.

        if ($topic->getId() == "acme/channel/shout")
            //shout something to all subs.
        */


        $topic->broadcast(array(
            "sender" => $conn->resourceId,
            "topic" => $topic->getId(),
            "event" => $event
        ));
    }

    /**
    * Like RPC is will use to prefix the channel
    * @return string
    */
    public function getPrefix()
    {
        return 'acme';
    }

}
```

The 4 methods that must be implemented are:

* `onSubscribe(Conn $conn, $topic)`
* `onUnSubscribe(Conn $conn, $topic)`
* `onPublish(Conn $conn, $topic, $event, array $exclude, array $eligible)`
* `getPrefix()`


* `$conn` is the connection object of the client who has initiated this event.
* `$topic` is the [Topic object](http://socketo.me/api/class-Ratchet.Wamp.Topic.html). This also contains a list of current subscribers, so you don't have to manually keep track.


##Step 2: Register your service with Symfony

If you are using **YML**, edit `YourBundle/Resources/config/services.yml`

For other formats, please check the [Symfony2 Documents](http://symfony.com/doc/master/book/service_container.html)

```yaml
services:
    acme_hello.topic_sample_service:
        class: Acme\HelloBundle\Topic\AcmeTopic
```

From now you can directly tag your service to register your service into GosWebSocket

```yaml
services:
    acme_hello.topic_sample_service:
        class: Acme\HelloBundle\Topic\AcmeTopic
        tags:
            - { name: gos_web_socket.topic }
```

**or** register via "app/config/config.yml"

```yaml
gos_web_socket:
    topic:
        - @acme_hello.topic_sample_service
```

### Retrive the current user

This feature needs some configurations, please check [Session Setup](SessionSetup.md) before continue and understand how it's work.

To retrieve the user connected through websocket, you must inject client storage service in your topic.

```yaml
services:
    acme_hello.topic_sample_service:
        class: Acme\HelloBundle\Topic\AcmeTopic
        arguments:
        	- @gos_web_socket.client_storage
        tags:
            - { name: gos_web_socket.topic }
```

```php
use Gos\Bundle\WebSocketBundle\Client\ClientStorage;

class AcmeTopic implements TopicInterface
{
    /**
     * @var ClientStorage
     */
    protected $clientStorage;

    /**
     * @param ClientStorage $clientStorage
     */
    public function __construct(ClientStorage $clientStorage)
    {
        $this->clientStorage = $clientStorage;
    }

    /**
     * @param ConnectionInterface $connection
     * @param Topic               $topic
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic)
    {
        /** @var UserInterface */
        $user = $this->clientStorage->getClient(ClientStorage::getStorageId($connection));
    }
}
```


##Step 3: Connect client to your topic
The following javascript will show connecting to this topic, notice how "acme/channel" will match the name "acme" we gave the service.

```javascript
    ...

    //the callback function in "subscribe" is called everytime an event is published in that channel.
    session.subscribe("acme/channel", function(uri, payload){
        console.log("Received message", payload.msg);
    });

    session.publish("acme/channel", {msg: "This is a message!"});

    session.unsubscribe("acme/channel");

    session.publish("acme/channel", {msg: "I won't see this"});
```

If we wanted to include dynamic channels based on that namespace, we could include them, and they will all get routed through that Topic Handler.

```javascript
    ...

    //the callback function in "subscribe" is called everytime an event is published in that channel.
    session.subscribe("acme/channel/id/12345", function(uri, payload){
        console.log("Received message", payload.msg);
    });

    session.publish("acme/channel/id/12345", {msg: "This is a message!"});
```
_Please note, this is not secure as anyone can subscribe to these channels by making a request for them. For true private channels, you will need to implement server side security_

For more information on the Client Side of Gos WebSocket, please see [Client Side Setup](ClientSetup.md)
