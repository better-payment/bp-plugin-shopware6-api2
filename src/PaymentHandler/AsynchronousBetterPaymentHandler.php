<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\Util\BetterPaymentClient;
use BetterPayment\Util\PaymentStatusMapper;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AsynchronousBetterPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    private PaymentStatusMapper $paymentStatusMapper;
    private BetterPaymentClient $betterPaymentClient;

    public function __construct(
        PaymentStatusMapper $paymentStatusMapper,
        BetterPaymentClient $betterPaymentClient,
    ){
        $this->paymentStatusMapper = $paymentStatusMapper;
        $this->betterPaymentClient = $betterPaymentClient;
    }

    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            $redirectUrl = $this->betterPaymentClient->request($transaction, $salesChannelContext->getContext())->action_data->url;
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL
                . $e->getMessage() . PHP_EOL
                . 'TRACE: ' . $e->getTraceAsString()
            );
        }

        // Redirect to external gateway
        return new RedirectResponse($redirectUrl);
    }

    // When it returns to success url, update the payment status
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $context = $salesChannelContext->getContext();
        $betterPaymentTransactionId = $transaction->getOrderTransaction()->getCustomFields()['better_payment_transaction_id'];
        $status = $this->betterPaymentClient->getBetterPaymentTransaction($betterPaymentTransactionId)['status'];
        $this->paymentStatusMapper->updateOrderTransactionStateFromPaymentHandler($transaction->getOrderTransaction()->getId(), $status, $context);
    }
}