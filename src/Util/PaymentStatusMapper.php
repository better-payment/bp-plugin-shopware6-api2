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
            // TODO: In case of SEPA Direct Debit and B2B Direct Debit, the chargeback status may come in,
            // when the Shopware Transaction's state is in_process. In this case, we have to add a custom flow
            // to mark this transaction as FAIL, as transition from in_progress to chargeback is not possible.
            case 'chargeback':
                $this->orderTransactionStateHandler->chargeback($orderTransactionID, $context);
                break;
            case 'declined':
            case 'error':
                $this->orderTransactionStateHandler->fail($orderTransactionID, $context);
                break;
            // TODO: In case captured_amount has been received in the postback notification, pass it here.
            // Check if captured_amount is NIL, if yes, then call process. 
            // If captured_amount is NOT nill or NON ZERO, then call payPartially.
            case 'pending':
                $this->orderTransactionStateHandler->process($orderTransactionID, $context);
                break;
            case 'completed':
                $this->orderTransactionStateHandler->paid($orderTransactionID, $context);
                break;
            // When we receive `refunded` status from BP, send a query to GET transactions/:id and identify
            // the amount that has been totally refunded. If this amount is greater than or equals to the transaction
            // amount in shopware, call refund, otherwise, call refundPartially.
            case 'refunded':
                if (true)
                    $this->orderTransactionStateHandler->refund($orderTransactionID, $context);
                else
                    $this->orderTransactionStateHandler->refundPartially($orderTransactionID, $context);
                break;
            // TODO: In case an unidentified status is received, we should raise an exception, so the BP
            // recives a 500 or 400 error in the response. This way, BP will see that something is wrong
            // in sending certain postbacks to shopware.
            default:
                $this->orderTransactionStateHandler->reopen($orderTransactionID, $context);
        }
    }
}
