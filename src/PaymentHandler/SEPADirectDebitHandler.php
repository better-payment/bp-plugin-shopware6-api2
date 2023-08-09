<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\Util\BetterPaymentClient;
use BetterPayment\Util\PaymentStatusMapper;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SEPADirectDebitHandler implements SynchronousPaymentHandlerInterface
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

    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        try {
            $status = $this->betterPaymentClient->request($transaction, $dataBag)->status;
            $context = $salesChannelContext->getContext();

            $this->paymentStatusMapper->updateOrderTransactionStateFromPaymentHandler($transaction->getOrderTransaction()->getId(), $status, $context);
        } catch (\Exception $e) {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL
                . $e->getMessage() . PHP_EOL
                . 'TRACE: ' . $e->getTraceAsString()
            );
        }
    }
}