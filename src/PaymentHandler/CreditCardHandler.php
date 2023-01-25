<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\Util\BetterPaymentClient;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\RefundPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class CreditCardHandler implements AsynchronousPaymentHandlerInterface/*, RefundPaymentHandlerInterface*/
{
    private OrderTransactionStateHandler $orderTransactionStateHandler;
//    private OrderTransactionCaptureRefundStateHandler $refundStateHandler;
    private BetterPaymentClient $betterPaymentClient;

    public function __construct(
        OrderTransactionStateHandler $orderTransactionStateHandler,
        OrderTransactionCaptureRefundStateHandler $refundStateHandler,
        BetterPaymentClient $betterPaymentClient
    )
    {
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
//        $this->refundStateHandler = $refundStateHandler;
        $this->betterPaymentClient = $betterPaymentClient;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            $redirectUrl = $this->betterPaymentClient->request($transaction);
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }

        // Redirect to external gateway
        return new RedirectResponse($redirectUrl);
    }

    /**
     * @inheritDoc
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        // When it returns to success url mark payment as paid
        $context = $salesChannelContext->getContext();
        // Payment completed, set transaction status to "paid"
        $this->orderTransactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
    }

//    public function refund(string $refundId, Context $context): void
//    {
//        $this->refundStateHandler->complete($refundId, $context);
//    }
}