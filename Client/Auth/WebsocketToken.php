<?php

namespace Gos\Bundle\WebSocketBundle\Client\Auth;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class WebsocketToken extends AbstractToken
{
    /**
     * @var \DateTimeImmutable
     */
    protected $created;

    /**
     * @param array $roles
     */
    public function __construct(array $roles = array())
    {
        parent::__construct($roles);
        $this->created = new \DateTimeImmutable();

        $this->setAuthenticated(count($roles) > 0);
    }

    /**
     *
     */
    public function getCredentials()
    {
        // TODO: Implement getCredentials() method.
    }
}
