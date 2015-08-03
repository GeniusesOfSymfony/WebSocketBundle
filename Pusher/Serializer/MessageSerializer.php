<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Serializer;

use Gos\Bundle\WebSocketBundle\Pusher\MessageInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class MessageSerializer
{
    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var NormalizerInterface[]
     */
    protected $normalizers;

    /** @var  string */
    protected $class;

    /**
     * @var EncoderInterface[]
     */
    protected $encoders;

    public function __construct()
    {
        $this->normalizers = [
            new GetSetMethodNormalizer(),
        ];

        $this->encoders = [
            new JsonEncoder(),
        ];

        $this->serializer = new Serializer($this->normalizers, $this->encoders);
    }

    /**
     * @param MessageInterface $message
     *
     * @return string|\Symfony\Component\Serializer\Encoder\scalar
     */
    public function serialize(MessageInterface $message)
    {
        $this->class = get_class($message);

        return $this->serializer->serialize($message, 'json');
    }

    public function deserialize($data)
    {
        $class = null === $this->class ? 'Gos\Bundle\WebSocketBundle\Pusher\Message' : $this->class;

        return $this->serializer->deserialize($data, $class, 'json');
    }
}
