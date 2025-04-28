<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\Util\BetterPaymentClient;
use BetterPayment\Util\PaymentStatusMapper;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ApplePayPaymentHandler extends AbstractPaymentHandler
{
    private PaymentStatusMapper $paymentStatusMapper;
    private BetterPaymentClient $betterPaymentClient;

    public function __construct(
        PaymentStatusMapper $paymentStatusMapper,
        BetterPaymentClient $betterPaymentClient
    ){
        $this->paymentStatusMapper = $paymentStatusMapper;
        $this->betterPaymentClient = $betterPaymentClient;
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return false;
    }

    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): ?RedirectResponse
    {
        $status = $request->get("betterpayment_transaction_status");
        $this->paymentStatusMapper->updateOrderTransactionStateFromPaymentHandler($transaction->getOrderTransactionId(), $status, $context);

        $this->betterPaymentClient->storeBetterPaymentTransactionID($transaction->getOrderTransactionId(), $request->get('betterpayment_transaction_id'));

        return null;
    }
}