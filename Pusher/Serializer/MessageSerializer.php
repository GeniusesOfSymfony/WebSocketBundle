<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Serializer;

use Gos\Bundle\WebSocketBundle\Pusher\Message;
use Gos\Bundle\WebSocketBundle\Pusher\MessageInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

final class MessageSerializer
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var string
     */
    private $class;

    public function __construct()
    {
        $normalizers = [
            new GetSetMethodNormalizer(),
        ];

        $encoders = [
            new JsonEncoder(),
        ];

        $this->serializer = new Serializer($normalizers, $encoders);
    }

    /**
     * @return string
     */
    public function serialize(MessageInterface $message)
    {
        $this->class = \get_class($message);

        return $this->serializer->serialize($message, 'json');
    }

    public function deserialize($data)
    {
        $class = null === $this->class ? Message::class : $this->class;

        return $this->serializer->deserialize($data, $class, 'json');
    }
}
