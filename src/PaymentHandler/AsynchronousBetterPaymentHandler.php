<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\Util\BetterPaymentClient;
use BetterPayment\Util\OrderParametersReader;
use BetterPayment\Util\PaymentStatusMapper;
use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AsynchronousBetterPaymentHandler extends AbstractPaymentHandler
{
    private OrderParametersReader $orderParametersReader;
    private BetterPaymentClient $betterPaymentClient;
    private PaymentStatusMapper $paymentStatusMapper;
    private EntityRepository $orderTransactionRepository;

    public function __construct(
        OrderParametersReader $orderParametersReader,
        BetterPaymentClient $betterPaymentClient,
        PaymentStatusMapper $paymentStatusMapper,
        EntityRepository $orderTransactionRepository,
    ){
        $this->orderParametersReader = $orderParametersReader;
        $this->betterPaymentClient = $betterPaymentClient;
        $this->paymentStatusMapper = $paymentStatusMapper;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return false;
    }

    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): ?RedirectResponse
    {
        try {
            $parameters = $this->orderParametersReader->getAllParameters($request, $transaction, $context);
            $responseBody = $this->betterPaymentClient->requestPayment($parameters);
            $this->storeBetterPaymentTransactionId($transaction->getOrderTransactionId(), $responseBody['transaction_id'], $context);
            $redirectUrl = $responseBody['action_data']['url'];
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransactionId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }

        // Redirect to external gateway
        return new RedirectResponse($redirectUrl);
    }

    // When it returns to success url, update the payment status
    public function finalize(Request $request, PaymentTransactionStruct $transaction, Context $context): void
    {
        /* @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search(
            new Criteria([$transaction->getOrderTransactionId()]),
            $context
        )->first();

        $betterPaymentTransactionId = $orderTransaction->getCustomFields()['better_payment_transaction_id'];
        $status = $this->betterPaymentClient->getTransaction($betterPaymentTransactionId)['status'];
        $this->paymentStatusMapper->updateOrderTransactionStateFromPaymentHandler($transaction->getOrderTransactionId(), $status, $context);
    }

    private function storeBetterPaymentTransactionId(string $orderTransactionId, string $betterPaymentTransactionId, Context $context): void
    {
        $this->orderTransactionRepository->update([
            [
                'id' => $orderTransactionId,
                'customFields' => [
                    'better_payment_transaction_id' => $betterPaymentTransactionId
                ]
            ]
        ], $context);
    }
}