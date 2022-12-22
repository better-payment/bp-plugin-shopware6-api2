<?php declare(strict_types=1);

namespace BetterPayment\PaymentHandler;

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
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class CreditCardHandler implements AsynchronousPaymentHandlerInterface
{
    private OrderTransactionStateHandler $orderTransactionStateHandler;
//    private SystemConfigService $systemConfigService;
    private Client $client;

    public function __construct(OrderTransactionStateHandler $orderTransactionStateHandler/*, SystemConfigService $systemConfigService*/)
    {
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
//        $this->systemConfigService = $systemConfigService;
        $this->client = new Client();
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
//        dd($this->systemConfigService->get('BetterPayment.config.environment'));
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            // TODO remove below temporary line for url shortening
            $this->client->post('https://lightfulweb.free.beeceptor.com/returnurl', [
                'body' => $transaction->getReturnUrl()
            ]);
            $redirectUrl = $this->sendReturnUrlToExternalGateway($transaction->getReturnUrl());
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
        $transactionId = $transaction->getOrderTransaction()->getId();

        // Example check if the user cancelled. Might differ for each payment provider
        if ($request->query->getAlpha('status') == 'canceled') {
            throw new CustomerCanceledAsyncPaymentException(
                $transactionId,
                'Customer canceled the payment on the payment page'
            );
        }

        // Example check for the actual status of the payment. Might differ for each payment provider
        $paymentState = $request->query->getAlpha('status');

        $context = $salesChannelContext->getContext();
        if ($paymentState === 'completed') {
            // Payment completed, set transaction status to "paid"
            $this->orderTransactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
        } else {
            // Payment not completed, set transaction status to "open"
            $this->orderTransactionStateHandler->reopen($transaction->getOrderTransaction()->getId(), $context);
        }
    }

    private function sendReturnUrlToExternalGateway(string $getReturnUrl): string
    {
        // Do some API Call to your payment provider
        $headers = [
            'Authorization' => 'Basic NzBhYmQ1OTQwODQ3ODdhMzkyZTg6NGE2NmI5MWU5YjVjOTBjYTQ3YjA=',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $options = [
            'form_params' => [
                'payment_type' => 'cc',
                'amount' => '15.9',
                'postback_url' => 'https://lightfulweb.free.beeceptor.com/bp-cc',
                'success_url' => substr($getReturnUrl,0, 255), // TODO this needs to be less than 255 chars
                'error_url' => substr($getReturnUrl,0, 255),
                'country' => 'DE',
            ]
        ];

        $request = new GuzzleRequest('POST', 'https://devapi.betterpayment.de/rest/payment', $headers);
//        $request = new GuzzleRequest('POST', 'https://lightfulweb.free.beeceptor.com/bp-cc', $headers);
        try {
            $response = $this->client->send($request, $options);
            if ($response->getStatusCode() == 200) { // TODO almost all responses are 200 coded, so check and handle them accordingly
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