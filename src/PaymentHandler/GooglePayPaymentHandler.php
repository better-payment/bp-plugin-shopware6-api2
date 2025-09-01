<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\Util\PaymentStatusMapper;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class GooglePayPaymentHandler extends AbstractPaymentHandler
{
    private PaymentStatusMapper $paymentStatusMapper;
    private EntityRepository $orderTransactionRepository;

    public function __construct(
        PaymentStatusMapper $paymentStatusMapper,
        EntityRepository $orderTransactionRepository,
    ) {
        $this->paymentStatusMapper = $paymentStatusMapper;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return false;
    }

    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): ?RedirectResponse
    {
        $this->storeBetterPaymentTransactionId($transaction->getOrderTransactionId(), $request->get('betterpayment_google_pay_transaction_id'), $context);
        $this->storeBetterPaymentGooglePayOrderId($transaction->getOrderTransactionId(), $request->get('betterpayment_google_pay_order_id'), $context);
        $this->paymentStatusMapper->updateOrderTransactionStateFromPaymentHandler($transaction->getOrderTransactionId(), $request->get('betterpayment_google_pay_transaction_status'), $context);

        // SyncPaymentHandler, so no redirect
        return null;
    }

    private function storeBetterPaymentTransactionId(string $orderTransactionId, string $betterPaymentTransactionId, Context $context): void
    {
        $this->orderTransactionRepository->update([
            [
                'id' => $orderTransactionId,
                'customFields' => [
                    'better_payment_transaction_id' => $betterPaymentTransactionId
                ]
            ]
        ], $context);
    }

    // Set Google Pay order id as custom field to order, so that it can be matched with order in payment gateway
    // Here we cannot use core (by shopware) order number. Because of the flow, the order is created after request sent to payment gateway
    private function storeBetterPaymentGooglePayOrderId(string $orderTransactionId, string $betterPaymentApplePayOrderId, Context $context): void
    {
        $this->orderTransactionRepository->update([
            [
                'id' => $orderTransactionId,
                'customFields' => [
                    'betterpayment_apple_pay_order_id' => $betterPaymentApplePayOrderId,
                ]
            ]
        ], $context);
    }
}