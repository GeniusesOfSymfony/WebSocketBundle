# HandshakeMiddleware

You can add any middleware as service to server with your business logic  
 

**Bundle Configuration**

```yaml
# Gos Web Socket Bundle
gos_web_socket:
    server:
        handshake_middleware: 
            - @some_service # have to extends Gos\Bundle\WebSocketBundle\Server\App\Stack\HandshakeMiddlewareAbstract
```
 


### Handshake middleware example for OAuth 

```php
<?php

namespace WebSocketBundle\Service\Middleware;

use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\HandshakeMiddlewareAbstract;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;


class OAuthMiddleware extends HandshakeMiddlewareAbstract
{
    /**
     * @var OAuth2
     */
    protected $oAuthService;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $firewalls;

    /**
     * @var SecurityContextInterface|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * OAuthMiddleware constructor.
     * @param EventDispatcherInterface $eventDispatcher
     * @param OAuth2 $oAuthService
     * @param array $firewalls
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        OAuth2 $oAuthService,
        $firewalls = array(),
        $tokenStorage
    )
    {
        $this->oAuthService = $oAuthService;
        $this->eventDispatcher = $eventDispatcher;
        $this->firewalls = $firewalls;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param ConnectionInterface $conn
     * @param RequestInterface|null $request
     *
     * @return void
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        try {
            $accessToken = $this->oAuthService->verifyAccessToken($request->getQuery()->get('access_token'));
        } catch (OAuth2AuthenticateException $e) {
            $this->eventDispatcher->dispatch(
                Events::CLIENT_REJECTED,
                new ClientRejectedEvent($e->getMessage(), $request)
            );

            $this->close($conn, 403);
            return ;
        }

        $user = $accessToken->getUser();
        $token = new AnonymousToken(
            $request->getQuery()->get('access_token'),
            $user,
            $user->getRoles()
        );
        $this->tokenStorage->setToken($token);

        return $this->_component->onOpen($conn, $request);
    }

    /**
     * Close a connection with an HTTP response.
     *
     * @param \Ratchet\ConnectionInterface $conn
     * @param int $code HTTP status code
     */
    protected function close(ConnectionInterface $conn, $code = 400)
    {
        $response = new Response($code, [
            'X-Powered-By' => \Ratchet\VERSION,
        ]);

        $conn->send((string)$response);
        $conn->close();
    }
}
```