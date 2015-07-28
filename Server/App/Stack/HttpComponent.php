<?php

/**
 * Created by PhpStorm.
 * User: johann
 * Date: 11/07/15
 * Time: 00:36.
 */
namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

class HttpComponent
{
    protected $ioComponent;

    public function __construct(IoComponent $ioComponent)
    {
        $this->ioComponent = $ioComponent;
    }
}
