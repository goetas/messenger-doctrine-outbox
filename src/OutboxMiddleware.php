<?php

namespace Goetas\MessengerDoctrineOutbox;

use Goetas\MessengerDoctrineOutbox\Stamp\FromOutboxStamp;
use Goetas\MessengerDoctrineOutbox\Stamp\OutboxStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;

class OutboxMiddleware implements MiddlewareInterface
{
    private $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (!$envelope->all(ReceivedStamp::class)) {

            // message published for the first time, it will be stored directly to the outbox transport
            $envelope = $envelope->with(new OutboxStamp());

            return $this->transport->send($envelope)
                // append the ReceivedStamp to avoid that it gets processed by the core "sender middleware"
                ->with(new ReceivedStamp(OutboxStamp::class));
        } elseif ($envelope->all(OutboxStamp::class)) {
            // message read from the outbox, publish it again and
            $envelope = $envelope
                // remove the ReceivedStamp to ensure that it gets processed by "handler middleware" (delivered to the new transport)
                ->withoutAll(ReceivedStamp::class)
                // remove the OutboxStamp to ensure that we do not repeat this again
                ->withoutAll(OutboxStamp::class)
                // add a marker stamp to signal that this message was in the outbox before
                ->with(new FromOutboxStamp());

            return $stack->next()->handle($envelope, $stack)
                // append the HandledStamp to avoid that it gets processed by the core "handler middleware"
                ->with(new HandledStamp(null, OutboxStamp::class));
        }

        // message read from message storage, process as usual
        return $stack->next()->handle($envelope, $stack);
    }
}
