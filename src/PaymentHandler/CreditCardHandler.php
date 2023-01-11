<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

use BetterPayment\Util\ConfigReader;
use BetterPayment\Util\OrderParametersReader;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class CreditCardHandler implements AsynchronousPaymentHandlerInterface
{
    private OrderTransactionStateHandler $orderTransactionStateHandler;
    private ConfigReader $configReader;
    private OrderParametersReader $orderParametersReader;
    private Client $client;

    public function __construct(
        OrderTransactionStateHandler $orderTransactionStateHandler,
        ConfigReader $configReader,
        OrderParametersReader $orderParametersReader
    )
    {
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->configReader = $configReader;
        $this->orderParametersReader = $orderParametersReader;
//        $this->client = new Client();
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            $redirectUrl = $this->sendRequestToExternalGateway($transaction);
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }

        // Redirect to external gateway
        return new RedirectResponse($redirectUrl);
    }

    /**
     * @inheritDoc
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
//        $transactionId = $transaction->getOrderTransaction()->getId();

        // Example check if the user cancelled. Might differ for each payment provider
//        if ($request->query->getAlpha('status') == 'canceled') {
//            throw new CustomerCanceledAsyncPaymentException(
//                $transactionId,
//                'Customer canceled the payment on the payment page'
//            );
//        }

        // When it returns to success url mark payment as paid
        $context = $salesChannelContext->getContext();
        // Payment completed, set transaction status to "paid"
        $this->orderTransactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
    }

    private function sendRequestToExternalGateway(AsyncPaymentTransactionStruct $transaction): string
    {
        $orderParameters = $this->orderParametersReader->getAllParameters($transaction);
        dd($orderParameters);

//        dd($transaction);
        // Do some API Call to your payment provider
        $headers = [
            'Authorization' => 'Basic REDACTED=',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $options = [
            'form_params' => [
                'payment_type' => 'cc',
                'postback_url' => 'https://lightfulweb.free.beeceptor.com/bp-cc',
                'success_url' => $transaction->getReturnUrl(),
                'error_url' => 'http://localhost:8888/lightfulweb/shopware6/public/account/order/edit/'
                    .$transaction->getOrder()->getId()
                    .'?error-code=CHECKOUT__ASYNC_PAYMENT_PROCESS_INTERRUPTED', // TODO edit this temp url
            ]
        ];

        $request = new GuzzleRequest('POST', $this->configReader->getAPIHostName().'/rest/payment', $headers);
        try {
            $response = $this->client->send($request, $options);
            if ($response->getStatusCode() == 200) { // TODO almost all responses are 200 code, so check and handle them accordingly
                return json_decode((string) $response->getBody())->action_data->url;
            }
            else {
                return 'https://error.test.com';
            }
        }
        catch(GuzzleException $e) {
            return 'https://guzzlerror.test.com';
        }
    }
}