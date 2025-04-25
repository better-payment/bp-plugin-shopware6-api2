<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\Util\BetterPaymentClient;
use BetterPayment\Util\PaymentStatusMapper;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
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
        $status = 'completed';

        $this->paymentStatusMapper->updateOrderTransactionStateFromPaymentHandler($transaction->getOrderTransactionId(), $status, $context);
        return null;
    }
}