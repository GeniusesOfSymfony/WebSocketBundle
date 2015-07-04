#Topic Handler Setup

Although the standard Gos WebSocket PubSub can be useful as a simple channel for allowing messages to be pushed to users, anymore advanced functionality will require custom Topic Handlers.

Similar to RPC, topic handlers are slightly specialised Symfony2 services. They must implement the interface `Gos\Bundle\WebSocketBundle\Topic\TopicInterface`

### What is a topic ?
A topic is the server side representation of a pubsub channel.

You just have to register a topic who catch all channel prefixed by `chat` to handle pubsub. A topic can only support one prefix.

##Overview

* Create the topic handler service
* Register your service with symfony
* Connect the client with your topic
* Link the topic with channel with pubsub router

##Step 1: Create the Topic Handler Service

```php
<?php

namespace Acme\HelloBundle\Topic;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;

class AcmeTopic implements TopicInterface
{
    /**
     * This will receive any Subscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @return void
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        //this will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast(['msg' => $connection->resourceId . " has joined " . $topic->getId()]);
    }

    /**
     * This will receive any UnSubscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @return voids
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        //this will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast(['msg' => $connection->resourceId . " has left " . $topic->getId()]);
    }


    /**
     * This will receive any Publish requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param $Topic topic
     * @param WampRequest $request
     * @param $event
     * @param array $exclude
     * @param array $eligibles
     * @return mixed|void
     */
    public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
    {
        /*
        	$topic->getId() will contain the FULL requested uri, so you can proceed based on that

            if ($topic->getId() == "acme/channel/shout")
     	       //shout something to all subs.
        */

        $topic->broadcast([
        	'msg' => $event
        ]);
    }

    /**
    * Like RPC is will use to prefix the channel
    * @return string
    */
    public function getName()
    {
        return 'acme.topic';
    }
}
```

### Most important things in topic

### Broadcast full definition

`Topic::broadcast($msg, array $exclude = array(), array $eligible = array());`

Send a message to all the connections in this topic.

**Note :** `$exclude` and `$include` work with Wamp Session ID available through `$connection->WAMP->sessionId`

#### How get the current channel information (route, attributes, path etc ...) ?

`$request->getRouteName()` Will give the mathed route name

`$request->getRoute()` will give [RouteInterface](https://github.com/GeniusesOfSymfony/PubSubRouterBundle/blob/master/Router/RouteInterface.php) object.

`$request->getAttributes()` will give [ParameterBag](http://api.symfony.com/2.6/Symfony/Component/HttpFoundation/ParameterBag.html)

For example, your channel pattern is `chat/user/{room}`, user subscribe to `chat/user/room1`

`$request->getAttributes()->get('room');` will return `room1`. You can look step3 who explain how pubsub router work.

`$topic->getId()` will return the subscribed channel e.g : `chat/user/room1`

#### How iterate over my subscribers ?

`Topic` implements `IteratorAggregate`, you can iterate over subscribers present in your topic. Client are reprensented by `ConnectionInterface`

```php
/** @var ConnectionInterface $client **/
foreach($topic as $client){
    //Do stuff ...
}
```

#### How send a message only to my current client ?

`$connection->event($topic->getId(), ['msg' => 'lol']);`

#### How count the number of subscribers I currently have ?

`Topic` implements `Countable` interface, you just have to do `count($topic)`

#### Topic interface & explaination

The **4 methods** that must be implemented are:

* `onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)` When client subscribe to the topic
* `onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)` When client unsubscribe to the topic
* `onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)` When client publish inside the topic
* `getName()` Use for routing definition, used to point this class.

Where
* `ConnectionInterface $connection` is the connection object of the client who has initiated this event.
* `TopicInterface $topic` is the [Topic object](http://socketo.me/api/class-Ratchet.Wamp.Topic.html). This also contains a list of current subscribers, so you don't have to manually keep track.
* `WampRequest` Is the representation of the request make trough websocket.

### Periodic Timer (Topic & Connection)

Topic periodic timer are active when at least one client is connected.

#### Attached on the topic (Means all subscribers are affected by this periodic timer)

You must implement `TopicPeriodicTimerInterface` to attach periodic timer to your `Topic` object.

> In other word, if you have something like : "Each 5 minutes all subscriber of my topic must recieve a message (ads, bot message etc...)" This feature is for that.

You will need to add these 2 functions :

* `setPeriodicTimer(TopicPeriodicTimer $periodicTimer)`
* `registerPeriodicTimer(Topic $topic)`

A trait is available to make your life easier, it implement `setPeriodicTimer` and related property: `Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerTrait`

```php
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface;

class AcmeTopic implements TopicInterface, TopicPeriodicTimerInterface
{
    /**
     * @var TopicPeriodicTimer
     */
    protected $periodicTimer;

    /**
     * @param TopicPeriodicTimer $periodicTimer
     */
    public function setPeriodicTimer(TopicPeriodicTimer $periodicTimer)
    {
        $this->periodicTimer = $periodicTimer;
    }

    /**
     * @param Topic $topic
     *
     * @return array
     */
    public function registerPeriodicTimer(Topic $topic)
    {
        //add
        $this->periodicTimer->addPeriodicTimer($this, 'hello', 2, function() use ($topic){
            $topic->broadcast('hello world');
        });

        //exist
        $this->periodicTimer->isPeriodicTimerActive($this, 'hello'); // true or false

        //remove
        $this->periodicTimer->cancelPeriodicTimer($this, 'hello');
    }
```

#### Attached on connection (means each client can have different periodic timer)

This feature allow you to use Periodic Timer to emit periodically to your client.

> In other word, if you have something like "Each 5 second this bob must recieve this message, and alice muste reciev another message each 10 minutes and then cancel timer of bob only" this feature is for you.

```php
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\ConnectionPeriodicTimer;

/**
 * @param  ConnectionInterface $connection
 * @param  Topic               $topic
 * @param WampRequest          $request
 */
public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
{
    /** @var ConnectionPeriodicTimer $topicTimer */
    $topicTimer = $connection->PeriodicTimer;

    //Add periodic timer
    $topicTimer->addPeriodicTimer('hello', 2, function() use ($topic, $connection) {
        $connection->event($topic->getId(), ['msg' => 'hello world']);
    });

    //exist
    $topicTimer->isPeriodicTimerActive('hello'); //true or false

    //Remove periodic timer
	$topicTimer->cancelPeriodicTimer('hello');
}
```

:collision: **Periodic timer is bind on the current connection ! So if you broadcast from periodic timer your clients will recieve this message x * the number of subscribers**

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
    topics:
        - @acme_hello.topic_sample_service
```

### Retrieve authenticated user through Symfony firewall

Look at [here](SessionSetup.md)

##Step 3: Connect client to your topics
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

##Step 4 : Link channel & topic with pubsub router

* Channel can be static e.g : `acme/user`
* Channel can be dynamic e.g : `acme/user/*`

Now will say to the system, topic named `acme.topic` (related to Topic::getName())` will handle channel pattern.

if he not already exists, create `AcmeBundle/Resources/config/pubsub/routing.yml` and register it in the websocket bundle configuration

**NOTE** : Don't forget to clear your cache take in account the new file.

```yaml
gos_web_socket:
    ...
    server:
        host: 127.0.0.1
        port: 8080
        router:
            resources:
                - @AcmeBundle/Resources/config/pubsub/routing.yml
    ...
```

Create the route to rely channel / topic

```yaml
acme_topic:
    channel: acme/channel
    handler:
        callback: 'acme.topic' #related to the getName() of your topic
```

###Advanced example

```yaml
acme_topic_chat:
    channel: acme/chat/{room}/{user_id}
    handler:
        callback: 'acme.topic' #related to the getName() of your topic
    requirements:
        room:
            pattern: "[a-z]+" #accept all valid regex, don't put delimiters !
        user_id:
            pattern: "\d+"
```

From here, each call who match with this pattern will handled by AcmeTopic.

This route will match `acme/chat/dev_room/123` and will be handled by topic named `acme.topic`

Another example, more complicated : 

```yaml
acme_topic_chat:
    channel: notification/user/{role}/{application}/{user}
    handler:
        callback: 'notification.topic'
    requirements:
        role:
            pattern: "editor|admin|client"
        application:
            pattern: "[a-z]+"
        user:
            pattern: "\d+"
            wildcard: true
```

Wildcard parameter allow you to match on this : `notification/user/admin/blog-app/*` and also `notification/user/admin/blog-app/all` and by the way `notification/user/admin/blog-app/1234` for notification system it can be usefull.

_Please note, this is not secure as anyone can subscribe to these channels by making a request for them. For true private channels, you will need to implement server side security_

For more information on the Client Side of Gos WebSocket, please see [Client Side Setup](ClientSetup.md)
