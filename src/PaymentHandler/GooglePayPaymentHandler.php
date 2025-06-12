<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\Util\BetterPaymentClient;
use BetterPayment\Util\PaymentStatusMapper;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GooglePayPaymentHandler implements SynchronousPaymentHandlerInterface
{
    private PaymentStatusMapper $paymentStatusMapper;
    private BetterPaymentClient $betterPaymentClient;
    private EntityRepository $orderRepository;

    public function __construct(
        PaymentStatusMapper $paymentStatusMapper,
        BetterPaymentClient $betterPaymentClient,
        EntityRepository $orderRepository,
    ){
        $this->paymentStatusMapper = $paymentStatusMapper;
        $this->betterPaymentClient = $betterPaymentClient;
        $this->orderRepository = $orderRepository;
    }

    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        try {
            $context = $salesChannelContext->getContext();

            $this->paymentStatusMapper->updateOrderTransactionStateFromPaymentHandler($transaction->getOrderTransaction()->getId(), $dataBag->get('betterpayment_google_pay_transaction_status'), $context);
            $this->betterPaymentClient->storeBetterPaymentTransactionID($transaction->getOrderTransaction()->getId(), $dataBag->get('betterpayment_google_pay_transaction_id'), $context);

            // Set Apple Pay order id as custom field to order, so that it can be matched with order in payment gateway
            // Here we cannot use core (by shopware) order number. Because of the flow, the order is created after request sent to payment gateway
            $this->orderRepository->update([
                [
                    'id' => $transaction->getOrder()->getId(),
                    // 'customFields' => [
                    //     'betterpayment_apple_pay_order_id' => $dataBag->get('betterpayment_apple_pay_order_id'),
                    // ]
                ]
            ], $context);
        } catch (\Exception $e) {
            throw PaymentException::syncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL
                . $e->getMessage() . PHP_EOL
                . 'TRACE: ' . $e->getTraceAsString()
            );
        }
    }
}