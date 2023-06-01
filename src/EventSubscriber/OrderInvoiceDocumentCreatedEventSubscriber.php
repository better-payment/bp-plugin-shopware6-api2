<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;


use BetterPayment\Util\BetterPaymentClient;
use BetterPayment\Util\ConfigReader;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderInvoiceDocumentCreatedEventSubscriber implements EventSubscriberInterface
{
    private BetterPaymentClient $betterPaymentClient;
    private EntityRepositoryInterface $orderRepository;
    private ConfigReader $configReader;

    public function __construct(BetterPaymentClient $betterPaymentClient, EntityRepositoryInterface $orderRepository, ConfigReader $configReader)
    {
        $this->betterPaymentClient = $betterPaymentClient;
        $this->orderRepository = $orderRepository;
        $this->configReader = $configReader;
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
        // TODO: check if payment method is invoice or invoiceB2B
		if ((true && $this->configReader->getBool(ConfigReader::INVOICE_AUTOMATICALLY_CAPTURE_ON_ORDER_INVOICE_DOCUMENT_SENT))
            || (true && $this->configReader->getBool(ConfigReader::INVOICE_B2B_AUTOMATICALLY_CAPTURE_ON_ORDER_INVOICE_DOCUMENT_SENT))) {
			$payloads = $event->getPayloads();
			foreach ($payloads as $payload) {
				if (isset($payload['config']['name']) && $payload['config']['name'] == 'invoice') {
					$orderId = $payload['orderId'];
					$invoiceId = $payload['config']['documentNumber'];

                    // Get order by orderId
                    $criteria = new Criteria([$orderId]);
                    /** @var OrderEntity $order */
                    $order = $this->orderRepository->search(
                        $criteria,
                        Context::createDefaultContext()
                    )->first();

                    // Make capture request parameters
                    $requestBody = [
                        'transaction_id' => 'add9343c-d372-4fec-9805-1de8107aad31', // TODO: fetch order transaction, then BP transaction id
                        'invoice_id' => $invoiceId,
                        'amount' => $order->getAmountTotal(),
                        'comment' => 'Captured using Shopware 6 plugin',
                    ];

                    // Send capture request
                    $this->betterPaymentClient->capture($requestBody);
				}
			}
		}
    }
}