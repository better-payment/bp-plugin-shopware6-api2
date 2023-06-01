<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;


use BetterPayment\PaymentMethod\Invoice;
use BetterPayment\PaymentMethod\InvoiceB2B;
use BetterPayment\Util\BetterPaymentClient;
use BetterPayment\Util\ConfigReader;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
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
		$payloads = $event->getPayloads();
		foreach ($payloads as $payload) {
			if (isset($payload['config']['name']) && $payload['config']['name'] == 'invoice') {
				// Get order by orderId
				$orderId = $payload['orderId'];
				$criteria = new Criteria([$orderId]);
				$criteria->addAssociation('transactions.paymentMethod');

				/** @var OrderEntity $order */
				$order = $this->orderRepository->search(
					$criteria,
					Context::createDefaultContext()
				)->first();

				$orderTransaction = $order->getTransactions()->last();

				if ($this->isCapturable($orderTransaction)) {
					$invoiceId = $payload['config']['documentNumber'];

					// Create capture request parameters
					$parameters = [
	                    'transaction_id' => $orderTransaction->getCustomFields()['better_payment_transaction_id'],
	                    'invoice_id' => $invoiceId,
	                    'amount' => $order->getAmountTotal(),
	                    'comment' => 'Captured using Shopware 6 plugin',
	                ];

					// Send capture request
					$this->betterPaymentClient->capture($parameters);
				}
			}
		}
    }

	public function isCapturable(OrderTransactionEntity $orderTransaction): bool
	{
		$paymentMethodShortname = $orderTransaction->getPaymentMethod()->getCustomFields()['shortname'];
		return ($paymentMethodShortname == Invoice::SHORTNAME && $this->configReader->getBool(ConfigReader::INVOICE_AUTOMATICALLY_CAPTURE_ON_ORDER_INVOICE_DOCUMENT_SENT))
		       || ($paymentMethodShortname == InvoiceB2B::SHORTNAME && $this->configReader->getBool(ConfigReader::INVOICE_B2B_AUTOMATICALLY_CAPTURE_ON_ORDER_INVOICE_DOCUMENT_SENT));
	}
}