<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\Util\BetterPaymentClient;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class GiropayHandler implements AsynchronousPaymentHandlerInterface
{
    private OrderTransactionStateHandler $orderTransactionStateHandler;
    private BetterPaymentClient $betterPaymentClient;

    public function __construct(
        OrderTransactionStateHandler $orderTransactionStateHandler,
        BetterPaymentClient $betterPaymentClient
    ){
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->betterPaymentClient = $betterPaymentClient;
    }

    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        try {
            $redirectUrl = $this->betterPaymentClient->request($transaction)->action_data->url;
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL
                . $e->getMessage() . PHP_EOL
                . 'TRACE: ' . $e->getTraceAsString()
            );
        }

        return new RedirectResponse($redirectUrl);
    }

    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $context = $salesChannelContext->getContext();
        $this->orderTransactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
    }
}