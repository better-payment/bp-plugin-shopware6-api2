<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\Util\BetterPaymentClient;
use BetterPayment\Util\OrderParametersReader;
use BetterPayment\Util\PaymentStatusMapper;
use Exception;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SynchronousBetterPaymentHandler extends AbstractPaymentHandler
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
    ) {
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
            $parameters = $this->orderParametersReader->getAllParameters($transaction, $context);
            $responseBody = $this->betterPaymentClient->requestPayment($parameters);
            $this->storeBetterPaymentTransactionId($transaction->getOrderTransactionId(), $responseBody['transaction_id'], $context);
            $this->paymentStatusMapper->updateOrderTransactionStateFromPaymentHandler($transaction->getOrderTransactionId(), $responseBody['status'], $context);
        } catch (Exception $e) {
            throw PaymentException::syncProcessInterrupted(
                $transaction->getOrderTransactionId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }

        // SyncPaymentHandler, so no redirect
        return null;
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