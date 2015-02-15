<?php
namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class ClientEventListener
{
    /**
     * @param ClientStorage $clientStorage
     */
    protected $clientStorage;

    /**
     * @var string[]
     */
    protected $firewalls;

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @param ClientStorage            $clientStorage
     * @param SecurityContextInterface $securityContext
     * @param array                    $firewalls
     */
    public function __construct(
        ClientStorage $clientStorage,
        SecurityContextInterface $securityContext,
        $firewalls = array()
    ) {
        $this->clientStorage = $clientStorage;
        $this->firewalls = $firewalls;
        $this->securityContext = $securityContext;
    }

    /**
     * Called whenever a client connects
     *
     * @param ClientEvent $event
     */
    public function onClientConnect(ClientEvent $event)
    {
        $conn = $event->getConnection();
        $token = null;

        if(isset($conn->Session) && $conn->Session){
            foreach($this->firewalls as $firewall){
                if(false !== $serializedToken = $conn->Session->get('_security_'.$firewall, false)){
                    /** @var TokenInterface $token */
                    $token = unserialize($serializedToken);
                    break;
                }
            }
        }

        if(null === $token){
            $token = new AnonymousToken($this->firewalls[0], $conn->resourceId);
        }

        $this->securityContext->setToken($token);
        $this->clientStorage->addClient($conn->resourceId, $token->getUser());

        echo $conn->resourceId . " connected" . PHP_EOL;
    }

    /**
     * Called whenever a client disconnects
     *
     * @param ClientEvent $event
     */
    public function onClientDisconnect(ClientEvent $event)
    {
        $conn = $event->getConnection();
        $this->clientStorage->removeClient($conn->resourceId);

        echo $conn->resourceId . " disconnected" . PHP_EOL;
    }

    /**
     * Called whenever a client errors
     *
     * @param ClientErrorEvent $event
     */
    public function onClientError(ClientErrorEvent $event)
    {
        $e = $event->getException();
        echo "connection error occurred: " . $e->getMessage() . PHP_EOL;
    }
}
