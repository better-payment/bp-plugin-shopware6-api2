<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;


use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderInvoiceDocumentCreatedEventSubscriber implements EventSubscriberInterface
{
	public function __construct()
    {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            'document.written' => 'onInvoiceDocumentCreated',
        ];
    }

    public function onInvoiceDocumentCreated(EntityWrittenEvent $event): void
    {
		// check if plugin config option is set to automatically capture on invoice document create
		if (true) {
			$payloads = $event->getPayloads();
			foreach ($payloads as $payload) {
				if (isset($payload['config']['name']) && $payload['config']['name'] == 'invoice') {
					$orderId = $payload['orderId'];
					$invoiceNumber = $payload['config']['documentNumber'];

					// TODO
					// fetch order entity and then get its total price
					// then send capture request to API endpoint
				}
			}
		}
    }
}