# Creating Topics

A topic is the server side representation of a PubSub channel.

In your Symfony application, topics are defined as services in your dependency injection container.

## Overview

- Create the service class
- Register your service with Symfony
- Add a route for your service
- Connect the client with your topic
- Additional features

## Step 1: Create the service class

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
     * Handles subscription requests for this topic.
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
    {
        // This will broadcast a message to all subscribers of this topic notifying them of the new subscriber.
        $topic->broadcast(['msg' => $connection->resourceId.' has joined '.$topic->getId()]);
    }

    /**
     * Handles unsubscription requests for this topic.
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
    {
        // This will broadcast a message to all subscribers of this topic notifying them of the unsubscribing user.
        $topic->broadcast(['msg' => $connection->resourceId.' has left '.$topic->getId()]);
    }

    /**
     * Handles publish requests for this topic.
     *
     * @param mixed $event The event data
     */
    public function onPublish(
        ConnectionInterface $connection,
        Topic $topic,
        WampRequest $request,
        $event,
        array $exclude,
        array $eligible
    ): void {
        // This will broadcast a message to all subscribers of this topic.
        $topic->broadcast(['msg' => $event]);
    }

    /**
     * Name of the topic.
     */
    public function getName(): string
    {
        return 'acme.topic';
    }
}
```
## Step 2: Register your service with Symfony

Topic handlers must be tagged with the `gos_web_socket.topic` tag to be correctly registered. Note that when autowiring is enabled, your service will be automatically tagged.

```yaml
# config/services.yaml
services:
    App\Websocket\Topic\AcmeTopic:
        tags:
            - { name: gos_web_socket.topic }
```

## Step 3: Add a route for your service

Now that you have created your Topic service, you must now add a route to the service so the bundle can route messages to your class. `acme/channel` will refer to the service you've created.

Using the configuration from the installation steps, you can add the following file to your Symfony application at `config/pubsub/websocket/routing.yaml`

```yaml
acme_topic:
    # A unique URI pattern to identify this topic handler 
    pattern: acme/channel

    # Must match the `getName` method of your Topic class
    callback: 'acme.rpc'
```

From here, each call that matches with this pattern will handled by the `AcmeTopic` class.

Similar to Symfony's Routing component, you can define multiple routes in a single file.

```yaml
acme_topic:
    pattern: acme/channel
    callback: 'acme.topic'
            
acme_secured_topic:
    pattern: acme/channel/secure
    callback: 'acme.secured.topic'
```

## Step 4: Connect the JavaScript client to your topics

The following JavaScript will show connecting to this topic, notice how "acme/channel" will match the name "acme" we gave the service.

```javascript
// The callback function in "subscribe" is called every time an event is published in that channel.
session.subscribe('acme/channel', function (uri, payload) {
    console.log('Received message', payload);
});

session.publish('acme/channel', {msg: 'This is a message!'});

session.unsubscribe('acme/channel');

session.publish('acme/channel', {msg: "I won't see this"});
```

For more information on the JavaScript Client the bundle, please see [Client Side Setup](javascript-client.md)

## Additional features

### Securing a topic

If your topic requires additional validation for users to connect to it, you can have your service class implement `Gos\Bundle\WebSocketBundle\Topic\SecuredTopicInterface` to add basic firewall functionality into your class.

The `SecuredTopicInterface` requires your Topic to implement one additional method:

- `public function secure(?ConnectionInterface $conn, Topic $topic, WampRequest $request, $payload = null, ?array $exclude = [], ?array $eligible = null, ?string $provider = null): void`

If a user is not allowed to connect to the topic, the `secure()` method *MUST* throw a `Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException`.

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
     * @param string|array $payload
     *
     * @throws FirewallRejectionException if the connection is not authorized access to the topic
     */
    public function secure(
        ?ConnectionInterface $conn,
        Topic $topic,
        WampRequest $request,
        $payload = null,
        ?array $exclude = [],
        ?array $eligible = null,
        ?string $provider = null
    ): void {
        // Check input data to verify if connection must be blocked
        if ($request->getAttributes()->has('denied')) {
            throw new FirewallRejectionException('Access denied');
        }

        // Access is granted
    }

    /**
     * Name of the topic.
     */
    public function getName(): string
    {
        return 'acme.secured.topic';
    }
}
```

### Periodic Timer (Topic & Connection)

Periodic timers are active when at least one client is connected. A periodic timer can be created on either a Topic or a Connection.

#### Topic Timers

Timers on a Topic are executed at a regular interval for as long as there is at least one client connected to that Topic (channel). Any actions taken in the periodic event will apply to all connected clients.

Your service must implement `Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface` to attach a periodic timer to your Topic.

To implement an example fulfilling a scenario of "every 5 minutes all subscribers of my topic must receive a message", the following will guide you on how to accomplish this.

You will need to add these two methods to your Topic:

- `public function registerPeriodicTimer(Topic $topic): void`
- `public function setPeriodicTimer(TopicPeriodicTimer $periodicTimer): void`

The `Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerTrait` is available to help implement the interface.

```php
<?php

namespace App\Websocket\Topic;

use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerTrait;
use Ratchet\Wamp\Topic;

class AcmePeriodicTopic extends AcmeTopic implements TopicPeriodicTimerInterface
{
    use TopicPeriodicTimerTrait;

    public function registerPeriodicTimer(Topic $topic): void
    {
        // Adds the periodic timer the first time a client connects to the topic
        $this->periodicTimer->addPeriodicTimer(
            $this,
            'hello',
            300,
            function () use ($topic) {
                $topic->broadcast('Hello world');
            }
        );

        // Checks if a timer has already been created
        $this->periodicTimer->isPeriodicTimerActive($this, 'hello'); // true or false

        // Removes an active timer
        $this->periodicTimer->cancelPeriodicTimer($this, 'hello');
    }

    /**
     * Name of the topic.
     */
    public function getName(): string
    {
        return 'acme.periodic.topic';
    }
}
```

#### Connection Timers

Timers on a Connection are executed at a regular interval for as long as the client is connected to the server. Any actions taken in the periodic event will apply only to the specific client.

A `$PeriodicTimer` property is added to the `Ratchet\ConnectionInterface` object when a client connects to the server, this object is a `Gos\Bundle\WebSocketBundle\Topic\ConnectionPeriodicTimer` object.

To implement an example fulfilling a scenario of "every 5 minutes the client must receive a message", the following will guide you on how to accomplish this.

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
     * Handles subscription requests for this topic.
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
    {
        // This will broadcast a message to all subscribers of this topic notifying them of the new subscriber.
        $topic->broadcast(['msg' => $connection->resourceId.' has joined '.$topic->getId()]);

        /** @var ConnectionPeriodicTimer $topicTimer */
        $topicTimer = $connection->PeriodicTimer;

        // Adds the periodic timer the first time a client connects to the topic
        $topicTimer->addPeriodicTimer(
            'hello',
            300,
            function () use ($connection, $topic) {
                // Broadcasts only to the current user
                $topic->broadcast('hello world', [], [$connection->WAMP->sessionId]);
            }
        );
    }

    /**
     * Handles unsubscription requests for this topic.
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
    {
        // This will broadcast a message to all subscribers of this topic notifying them of the unsubscribing user.
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
     * Name of the topic.
     */
    public function getName(): string
    {
        return 'acme.connection_periodic.topic';
    }
}
```
