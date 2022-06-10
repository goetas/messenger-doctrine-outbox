<?php

namespace Goetas\MessengerDoctrineOutbox;

use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransportFactory as BaseDoctrineTransportFactory;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;

class OutboxTransportFactory extends BaseDoctrineTransportFactory
{
    public function supports(string $dsn, array $options): bool
    {
        if (0 !== strpos($dsn, 'outbox-doctrine://')) {
            return false;
        }

        if (!isset($options['queue_name'])) {
            throw new InvalidArgumentException(sprintf('The "queue_name" option is mandatory for the "%s" outbox transport, the suggested value is "outbox".', $options['transport_name']));
        }
        return true;
    }
}
