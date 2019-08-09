# Topic Handler Setup

Although the standard GosWebSocketBundle PubSub can be useful as a simple channel for allowing messages to be pushed to users, more advanced functionality will require custom Topic Handlers.

Similar to RPC, topic handlers are specialized Symfony services. They must implement `Gos\Bundle\WebSocketBundle\Topic\TopicInterface`.

## What is a topic?

A topic is the server side representation of a PubSub channel.

You just have to register a topic who catch all channels prefixed by `chat` to handle PubSub. A topic can only support one prefix.

## Overview

* Create the topic handler service
* Register your service with Symfony
* Connect the client with your topic
* Link the topic with GosPubSubRouterBundle

## Step 1: Create the Topic Handler Service

Your service is a PHP class which must implement `Gos\Bundle\WebSocketBundle\Topic\TopicInterface`.

```php
<?php

namespace App\Websocket\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

class AcmeTopic implements TopicInterface
{
    /**
     * This will receive any Subscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     *
     * @return void
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        // This will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast(['msg' => $connection->resourceId.' has joined '.$topic->getId()]);
    }

    /**
     * This will receive any unsubscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     *
     * @return void
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        // This will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast(['msg' => $connection->resourceId.' has left '.$topic->getId()]);
    }

    /**
     * This will receive any Publish requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @param mixed $event
     * @param array $exclude
     * @param array $eligibles
     *
     * @return mixed
     */
    public function onPublish(
        ConnectionInterface $connection,
        Topic $topic,
        WampRequest $request,
        $event,
        array $exclude,
        array $eligible
    ) {
        /*
            $topic->getId() will contain the FULL requested uri, so you can proceed based on that

            if ($topic->getId() == "acme/channel/shout")
               //shout something to all subs.
        */

        $topic->broadcast(
            [
                'msg' => $event,
            ]
        );
    }

    /**
     * Like RPC the name is used to identify the channel
     *
     * @return string
     */
    public function getName()
    {
        return 'acme.topic';
    }
}
```

### Accessing request information

`$request->getRouteName()` Will give the matched route name

`$request->getRoute()` will give a [RouteInterface](https://github.com/GeniusesOfSymfony/PubSubRouterBundle/blob/v0.3.5/Router/RouteInterface.php) object with information about the current route.

`$request->getAttributes()` will give a [ParameterBag](https://github.com/symfony/symfony/blob/master/src/Symfony/Component/HttpFoundation/ParameterBag.php) object with the request attributes.

For example, your channel pattern is `chat/user/{room}` and the user subscribes to `chat/user/room1`

`$request->getAttributes()->get('room');` will return `room1`.

`$topic->getId()` will return the subscribed channel (`chat/user/room1`)

### How to iterate over my subscribers?

`Ratchet\Wamp\Topic` implements `IteratorAggregate`, whic allows you to iterate over subscribers present in your topic. Clients are reprensented by a `Ratchet\ConnectionInterface` object.

```php
/** @var ConnectionInterface $client */
foreach ($topic as $client) {
    // Do stuff ...
}
```

## Topic interface & explaination

The **4 methods** that must be implemented are:

* `onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)` is triggered when a client subscribes to a topic
* `onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)` is triggered when a client unsubscribes from a topic
* `onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)` is triggered when a client publishes a message to the topic
* `getName()` is used to identify the topic inside the bundle's services.

Where
* `ConnectionInterface $connection` is the [Connection object](http://socketo.me/api/class-Ratchet.ConnectionInterface.html) of the client who has initiated this event.
* `TopicInterface $topic` is the [Topic object](http://socketo.me/api/class-Ratchet.Wamp.Topic.html). This also contains a list of current subscribers, so you don't have to manually keep track.
* `WampRequest` Is the representation of the request made to the websocket server.

## Firewall setup (Topic)

It is possible to extend Topic services to exclude unwanted connections. Your service must implement `Gos\Bundle\WebSocketBundle\Topic\SecuredTopicInterface` to implement firewall functionality into your Topic object.

The `SecuredTopicInterface` requires your Topic to implement one additional method:

* `secure(ConnectionInterface $conn = null, Topic $topic, WampRequest $request, $payload = null, $exclude = null, $eligible = null, $provider = null)`

If a connection is not allowed to the topic, the `secure()` method *MUST* throw a `Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException`.

An example implementation is the following:

```php
<?php

namespace App\Websocket\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use Gos\Bundle\WebSocketBundle\Topic\SecuredTopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

class AcmeSecuredTopic extends AcmeTopic implements SecuredTopicInterface
{
    /**
     * @param ConnectionInterface $conn
     * @param Topic               $topic
     * @param null|string         $payload
     * @param string[]|null       $exclude
     * @param string[]|null       $eligible
     * @param string|null         $provider
     *
     * @return void
     */
    public function secure(ConnectionInterface $conn = null, Topic $topic, WampRequest $request, $payload = null, $exclude = null, $eligible = null, $provider = null)
    {
        // Check input data to verify if connection must be blocked
        if ($request->getAttributes()->has('denied')) {
            throw new FirewallRejectionException('Access denied');
        }

        // Access is granted
    }

    /**
     * Like RPC the name is used to identify the channel
     *
     * @return string
     */
    public function getName()
    {
        return 'acme.secured.topic';
    }
}
```

## Periodic Timer (Topic & Connection)

Periodic timers are active when at least one client is connected. A periodic timer can be created on either a Topic or a Connection.

### Topic Timers

Timers on a Topic are executed at a regular interval for as long as there is at least one client connected to that Topic (channel). Any actions taken in the periodic event will apply to all connected clients.

Your service must implement `Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface` to attach a periodic timer to your Topic.

To implement an example fulfilling a scenario of "every 5 minutes all subscribers of my topic must recieve a message", the following will guide you on how to accomplish this.

You will need to add these two methods to your Topic:

* `registerPeriodicTimer(Topic $topic)`
* `setPeriodicTimer(TopicPeriodicTimer $periodicTimer)`

The `Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerTrait` is available to fulfill this requirement.

```php
<?php

namespace App\Websocket\Topic;

use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerTrait;
use Ratchet\Wamp\Topic;

class AcmePeriodicTopic extends AcmeTopic implements TopicPeriodicTimerInterface
{
    use TopicPeriodicTimerTrait;

    /**
     * @param Topic $topic
     *
     * @return array
     */
    public function registerPeriodicTimer(Topic $topic)
    {
        // Adds the periodic timer the first time a client connects to the topic
        $this->periodicTimer->addPeriodicTimer(
            $this,
            'hello',
            300,
            function () use ($topic) {
                $topic->broadcast('hello world');
            }
        );

        // Checks if a timer has already been created
        $this->periodicTimer->isPeriodicTimerActive($this, 'hello'); // true or false

        // Removes an active timer
        $this->periodicTimer->cancelPeriodicTimer($this, 'hello');
    }

    /**
     * Like RPC the name is used to identify the channel
     *
     * @return string
     */
    public function getName()
    {
        return 'acme.periodic.topic';
    }
}
```

### Connection Timers

Timers on a Connection are executed at a regular interval for as long as the client is connected to theserver. Any actions taken in the periodic event will apply only to the specific client.

A `$PeriodicTimer` property is added to the `Ratchet\ConnectionInterface` object when a client connects to the server, this object is a `Gos\Bundle\WebSocketBundle\Topic\ConnectionPeriodicTimer` object.

To implement an example fulfilling a scenario of "every 5 minutes the client must recieve a message", the following will guide you on how to accomplish this.

```php
<?php

namespace App\Websocket\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\ConnectionPeriodicTimer;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;

class AcmeConnectionPeriodicTopic extends AcmeTopic
{
    /**
     * This will receive any Subscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     *
     * @return void
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        // This will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast(['msg' => $connection->resourceId.' has joined '.$topic->getId()]);

        /** @var ConnectionPeriodicTimer $topicTimer */
        $topicTimer = $connection->PeriodicTimer;

        // Adds the periodic timer the first time a client connects to the topic
        $topicTimer->addPeriodicTimer(
            'hello',
            300,
            function () use ($connection, $topic) {
                // Broadcasts only to the current user
                $topic->broadcast('hello world', [], [$connection->resourceId]);
            }
        );
    }

    /**
     * This will receive any unsubscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     *
     * @return void
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        // This will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast(['msg' => $connection->resourceId.' has left '.$topic->getId()]);

        /** @var ConnectionPeriodicTimer $topicTimer */
        $topicTimer = $connection->PeriodicTimer;

        // Checks if a timer has been created
        if ($topicTimer->isPeriodicTimerActive('hello')) {
            // Removes an active timer
            $topicTimer->cancelPeriodicTimer('hello');
        }
    }

    /**
     * Like RPC the name is used to identify the channel
     *
     * @return string
     */
    public function getName()
    {
        return 'acme.connection_periodic.topic';
    }
}
```

## Step 2: Register your service with Symfony

For an application based on the Symfony Standard structure, you can register services in either your `app/config/services.yml` file or your bundle's `Resources/config/services.yml` file. For an application based on Symfony Flex, use the `config/services.yaml` file.

Topic handlers must be tagged with the `gos_web_socket.topic` tag to be correctly registered.

```yaml
services:
    app.websocket.topic.acme:
        class: App\Websocket\Topic\AcmeTopic
        tags:
            - { name: gos_web_socket.topic }
```

For other formats, please review the [Symfony Documentation](http://symfony.com/doc/master/book/service_container.html).

### Alternative Service Registration (Deprecated)

Alternatively, you can list your Topic services in the bundle's configuration file. Note, this method is deprecated and removed in GosWebSocketBundle 2.0.

```yaml
gos_web_socket:
    topics:
        - '@app.websocket.topic.acme'
```

## Step 3: Register your service with GosPubSubRouterBundle

Now that you have created your Topic service, you must now link the path with your service. `acme/channel` will refer to the service you've created.

If not already created, you should create a routing file for the GosPubSubRouterBundle configuration. For Symfony Standard, you should use either `app/config/pubsub/routing.yml` or your bundle's `Resources/config/pubsub/routing.yml`. For Symfony Flex, you should use `config/pubsub/routing.yaml`.

```yaml
acme_topic:
    channel: acme/channel
    handler:
        callback: 'acme.topic'
```

Next, you will need to include the new resource in the bundle's configuration to ensure the PubSub router is set up correctly.

```yaml
gos_web_socket:
    server:
        port: 8080
        host: 127.0.0.1
        router:
            resources:
                - '%kernel.project_dir%/config/pubsub/routing.yaml'
```

From here, each call that matches with this pattern will handled by the `AcmeTopic` class.

Similar to Symfony's Routing component, you can define multiple routes in a single file.

```yaml
acme_topic:
    channel: acme/channel
    handler:
        callback: 'acme.topic'
            
acme_secured_topic:
    channel: acme/channel/secure
    handler:
        callback: 'acme.secured.topic'
```

## Step 4: Connect client to your topics
The following javascript will show connecting to this topic, notice how "acme/channel" will match the name "acme" we gave the service.

```javascript
// The callback function in "subscribe" is called every time an event is published in that channel.
session.subscribe("acme/channel", function (uri, payload) {
    console.log("Received message", payload);
});

session.publish("acme/channel", {msg: "This is a message!"});

session.unsubscribe("acme/channel");

session.publish("acme/channel", {msg: "I won't see this"});
```

For more information on the JavaScript Client the bundle, please see [Client Side Setup](ClientSetup.md)
