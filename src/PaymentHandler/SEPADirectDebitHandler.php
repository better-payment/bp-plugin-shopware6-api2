<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\PaymentMethod\SEPADirectDebit;
use BetterPayment\Util\BetterPaymentClient;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SEPADirectDebitHandler implements SynchronousPaymentHandlerInterface
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

    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        dd($dataBag);
        try {
            $status = $this->betterPaymentClient->syncRequest($transaction, SEPADirectDebit::SHORTNAME, $dataBag);
        } catch (\Exception $e) {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }

        $context = $salesChannelContext->getContext();
        if ($status == 'started') {
            $this->orderTransactionStateHandler->process($transaction->getOrderTransaction()->getId(), $context);
        }
        else {
            $this->orderTransactionStateHandler->fail($transaction->getOrderTransaction()->getId(), $context);
        }
    }
}