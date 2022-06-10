# goetas/messenger-doctrine-outbox

This library provides a middleware and a transport to implement
the [Transactional outbox](https://microservices.io/patterns/data/transactional-outbox.html)
pattern for the [symfony/messenger](https://symfony.com/doc/current/messenger.html) component.

## Installation

The recommended installation is via  [Composer](https://getcomposer.org/).

```bash
composer require goetas/messenger-doctrine-outbox
```

## Configuration

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:

            # add to the list of transport the outbox transport
            outbox:
                dsn: 'outbox-doctrine://default' # "default" in this case is the name of your default doctrine connection
                options:
                    queue_name: outbox # the queue name is mandatory to avoid conflicts with doctrine transport


        buses:
            event.bus:
                middleware:
                    # add to your middlewares the outbox middleware service
                    - 'app.messenger_doctrine_outbox_middleware'

# config/services.yaml
services:
    # define your outbox middleware service
    app.messenger_doctrine_outbox_middleware:
        class: Goetas\MessengerDoctrineOutbox\OutboxMiddleware
        arguments:
            - '@messenger.transport.outbox'

    # define the outbox transport factory
    goetas.messenger_doctrine_outbox_middleware:
        class: Goetas\MessengerDoctrineOutbox\OutboxTransportFactory
        arguments:
            $registry: '@doctrine'
        tags:
            - { name: messenger.transport_factory }

```

## Usage

```php
// src/Controller/DefaultController.php
namespace App\Controller;

use App\Message\SmsNotification;
use App\Entity\Sms;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Messenger\MessageBusInterface;

class DefaultController extends AbstractController
{
    public function sendSms(MessageBusInterface $bus, EntityManagerInterface $em)
    {
        $sms = new Sms('hello', '123');

        $em->wrapInTransaction(function() {
            $em->persist($sms);
            $bus->dispatch(new SmsNotification('Look! I created a message!'));
        });
    }

    // if you do not want to use wrapInTransaction()...
    public function sendDifferentSms(MessageBusInterface $bus, EntityManagerInterface $em)
    {
        $sms = new Sms('hello', '123');

        try {
            $em->beginTransaction();

            $em->persist($sms);
            $em->flush();
            $bus->dispatch(new SmsNotification('Look! I created a message!'));

            $em->commit();
        } catch (\Throwable $exception) {
            $em->rollback();
        }
    }
}
```

## Running the outbox consumer

You can run only the outbox consumer  with this command:
```bash
bin/consone bin/console  messenger:consume outbox
```

You can also run all the consumers with the following command.
```bash
bin/consone bin/console  messenger:consume
```
