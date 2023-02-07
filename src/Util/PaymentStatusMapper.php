<?php declare(strict_types=1);

namespace BetterPayment\Util;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;

class PaymentStatusMapper
{
    private OrderTransactionStateHandler $orderTransactionStateHandler;

    public function __construct(OrderTransactionStateHandler $orderTransactionStateHandler)
    {
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
    }

    public function updateOrderTransactionState(string $orderTransactionID, string $betterPaymentTransactionState, Context $context): void
    {
        switch ($betterPaymentTransactionState) {
            case 'started':
                $this->orderTransactionStateHandler->reopen($orderTransactionID, $context);
                break;
            case 'authorized':
                $this->orderTransactionStateHandler->authorize($orderTransactionID, $context);
                break;
            case 'canceled':
                $this->orderTransactionStateHandler->cancel($orderTransactionID, $context);
                break;
            case 'chargeback':
                $this->orderTransactionStateHandler->chargeback($orderTransactionID, $context);
                break;
            case 'declined':
            case 'error':
                $this->orderTransactionStateHandler->fail($orderTransactionID, $context);
                break;
            case 'pending': // TODO check whether it is process in shopware or not
                $this->orderTransactionStateHandler->process($orderTransactionID, $context);
                break;
            case 'completed':
                // TODO check for amount left
                if (true)
                    $this->orderTransactionStateHandler->paid($orderTransactionID, $context);
                else
                    $this->orderTransactionStateHandler->payPartially($orderTransactionID, $context);
                break;
            case 'refunded':
                // TODO check for amount left
                if (true)
                    $this->orderTransactionStateHandler->refund($orderTransactionID, $context);
                else
                    $this->orderTransactionStateHandler->refundPartially($orderTransactionID, $context);
                break;
            default:
                $this->orderTransactionStateHandler->reopen($orderTransactionID, $context);
        }
    }
}
