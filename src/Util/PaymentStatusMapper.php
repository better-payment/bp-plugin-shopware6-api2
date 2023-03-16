<?php declare(strict_types=1);

namespace BetterPayment\Util;

use BetterPayment\PaymentHandler\SEPADirectDebitB2BHandler;
use BetterPayment\PaymentHandler\SEPADirectDebitHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentStatusMapper
{
    private OrderTransactionStateHandler $orderTransactionStateHandler;
    private BetterPaymentClient $betterPaymentClient;
    private EntityRepositoryInterface $orderTransactionRepository;

    public function __construct(
        OrderTransactionStateHandler $orderTransactionStateHandler,
        BetterPaymentClient $betterPaymentClient,
        EntityRepositoryInterface $orderTransactionRepository
    ){
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->betterPaymentClient = $betterPaymentClient;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    public function updateOrderTransactionStateFromWebhook(Request $request, Context $context): Response
    {
        $betterPaymentTransactionID = $request->get('transaction_id');
        $betterPaymentTransactionState = $request->get('status');
        $orderTransaction = $this->getOrderTransactionByBetterPaymentTransactionID($betterPaymentTransactionID, $context);

        if ($orderTransaction) {
            $orderTransactionId = $orderTransaction->getId();
            $successResponse = new Response($request->get('message'), 200);
            switch ($betterPaymentTransactionState) {
                case 'started':
                    $this->orderTransactionStateHandler->reopen($orderTransactionId, $context);
                    return $successResponse;
                case 'authorized':
                    $this->orderTransactionStateHandler->authorize($orderTransactionId, $context);
                    return $successResponse;
                case 'canceled':
                    $this->orderTransactionStateHandler->cancel($orderTransactionId, $context);
                    return $successResponse;
                // In case of SEPA Direct Debit and B2B Direct Debit, the chargeback status may come in,
                // when the Shopware Transaction's state is in_process. In this case, we have to add a custom flow
                // to mark this transaction as FAIL, as transition from in_progress to chargeback is not possible.
                case 'chargeback':
                    if (($orderTransaction->getPaymentMethod()->getHandlerIdentifier() == SEPADirectDebitHandler::class
                        || $orderTransaction->getPaymentMethod()->getHandlerIdentifier() == SEPADirectDebitB2BHandler::class)
                        && $orderTransaction->getStateMachineState()->getTechnicalName() == OrderTransactionStates::STATE_IN_PROGRESS)
                    {
                        $this->orderTransactionStateHandler->fail($orderTransactionId, $context);
                    }
                    else
                    {
                        $this->orderTransactionStateHandler->chargeback($orderTransactionId, $context);
                    }

                    return $successResponse;
                case 'declined':
                case 'error':
                    $this->orderTransactionStateHandler->fail($orderTransactionId, $context);
                    return $successResponse;
                case 'pending':
                    $capturedAmount = $request->get('captured_amount');
                    if (!$capturedAmount)
                        $this->orderTransactionStateHandler->process($orderTransactionId, $context);
                    elseif ($capturedAmount > 0) // TODO: user just else statement maybe ???
                        $this->orderTransactionStateHandler->payPartially($orderTransactionId, $context);
                    return $successResponse;
                case 'completed':
                    $this->orderTransactionStateHandler->paid($orderTransactionId, $context);
                    return $successResponse;
                // When we receive `refunded` status from BP, send a query to GET transactions/:id and identify
                // the amount that has been totally refunded. If this amount is greater than or equals to the transaction
                // amount in shopware, call refund, otherwise, call refundPartially.
                case 'refunded':
                    $transaction = $this->betterPaymentClient->getBetterPaymentTransaction($betterPaymentTransactionID);
                    $refundedAmount = $transaction->refunded_amount;
                    $amount = $transaction->amount;
                    if ($refundedAmount >= $amount)
                        $this->orderTransactionStateHandler->refund($orderTransactionId, $context);
                    else
                        $this->orderTransactionStateHandler->refundPartially($orderTransactionId, $context);
                    return $successResponse;
                default:
                    // In case an unidentified status is received, we should raise an exception, so the BP
                    // receives 400 error in the response. This way, BP will see that something is wrong
                    // in sending certain postbacks to shopware.
                    return new Response('Unidentified status is received', 400);
            }
        }
        else {
            return new Response('Transaction not found', 404);
        }
    }

    private function getOrderTransactionByBetterPaymentTransactionID(string $betterPaymentTransactionID, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.better_payment_transaction_id', $betterPaymentTransactionID));

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }

    public function updateOrderTransactionStateFromPaymentHandler(string $orderTransactionID, string $betterPaymentTransactionState, Context $context): void
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
            case 'pending':
                $this->orderTransactionStateHandler->process($orderTransactionID, $context);
                break;
            case 'completed':
                $this->orderTransactionStateHandler->paid($orderTransactionID, $context);
                break;
            case 'refunded':
                $this->orderTransactionStateHandler->refund($orderTransactionID, $context);
                break;
            default:
                $this->orderTransactionStateHandler->reopen($orderTransactionID, $context);
        }
    }
}
