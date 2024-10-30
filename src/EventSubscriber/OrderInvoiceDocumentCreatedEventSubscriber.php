<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;


use BetterPayment\PaymentMethod\Invoice;
use BetterPayment\PaymentMethod\InvoiceB2B;
use BetterPayment\Util\BetterPaymentClient;
use BetterPayment\Util\ConfigReader;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OrderInvoiceDocumentCreatedEventSubscriber
 *
 * This event subscriber listens to the 'document.written' event and performs actions when an invoice document is created.
 */
class OrderInvoiceDocumentCreatedEventSubscriber implements EventSubscriberInterface
{
    private BetterPaymentClient $betterPaymentClient;
    private EntityRepository $orderRepository;
    private ConfigReader $configReader;

    /**
     * OrderInvoiceDocumentCreatedEventSubscriber constructor.
     *
     * @param BetterPaymentClient $betterPaymentClient
     * @param EntityRepository $orderRepository
     * @param ConfigReader $configReader
     */
    public function __construct(BetterPaymentClient $betterPaymentClient, EntityRepository $orderRepository, ConfigReader $configReader)
    {
        $this->betterPaymentClient = $betterPaymentClient;
        $this->orderRepository = $orderRepository;
        $this->configReader = $configReader;
    }

    /**
     * Returns the subscribed events and their corresponding methods.
     *
     * @return array The array of subscribed events and their methods.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'document.written' => 'onInvoiceDocumentCreated',
        ];
    }

    /**
     * Performs following actions when an invoice document is created:
     * 1. confirms whether this is really invoice type document to go on
     * 2. fetches order from DB, and its corresponding successful(last) order transaction
     * 3. checks whether this order transaction is capturable
     * 4. sends capture request using BetterPaymentClient
     *
     * @param EntityWrittenEvent $event
     */
    public function onInvoiceDocumentCreated(EntityWrittenEvent $event): void
    {
		$payloads = $event->getPayloads();
		foreach ($payloads as $payload) {
			if (isset($payload['config']['name']) && $payload['config']['name'] == 'invoice') {
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
                    $invoiceDate = $payload['config']['documentDate'];

					$captureParameters = [
	                    'transaction_id' => $orderTransaction->getCustomFields()['better_payment_transaction_id'],
	                    'invoice_id' => $invoiceId,
	                    'amount' => $order->getAmountTotal(),
                        'execution_date' => $invoiceDate,
	                    'comment' => 'Captured using Shopware 6 plugin',
	                ];

					$this->betterPaymentClient->capture($captureParameters);
				}
			}
		}
    }

    /**
     * Checks if an order transaction is capturable based on the payment method and corresponding configuration flag.
     *
     * @param OrderTransactionEntity $orderTransaction
     * @return bool Whether the order transaction is capturable or not.
     */
    public function isCapturable(OrderTransactionEntity $orderTransaction): bool
    {
        $customFields = $orderTransaction->getPaymentMethod()->getCustomFields();
        if ($customFields === null || !isset($customFields['shortname'])) {
            return false;
        }

        $paymentMethodShortname = $customFields['shortname'];
        
        return ($paymentMethodShortname == Invoice::SHORTNAME 
                && $this->configReader->getBool(ConfigReader::INVOICE_AUTOMATICALLY_CAPTURE_ON_ORDER_INVOICE_DOCUMENT_SENT))
            || ($paymentMethodShortname == InvoiceB2B::SHORTNAME 
                && $this->configReader->getBool(ConfigReader::INVOICE_B2B_AUTOMATICALLY_CAPTURE_ON_ORDER_INVOICE_DOCUMENT_SENT));
    }

}