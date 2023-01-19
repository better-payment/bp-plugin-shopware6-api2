<?php

namespace BetterPayment\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;

class BetterPaymentClient
{
    private ConfigReader $configReader;
    private OrderParametersReader $orderParametersReader;
    private Client $client;

    public function __construct(ConfigReader $configReader, OrderParametersReader $orderParametersReader)
    {
        $this->configReader = $configReader;
        $this->orderParametersReader = $orderParametersReader;
        $this->client = new Client();
    }

    public function request(AsyncPaymentTransactionStruct $transaction): string
    {
        $orderParameters = $this->orderParametersReader->getAllParameters($transaction);
        $requestParameters = array_merge($orderParameters, [
            'payment_type' => 'cc',
            'risk_check_approval' => '1',
            'postback_url' => 'https://lightfulweb.free.beeceptor.com/bp-cc',
            'success_url' => $transaction->getReturnUrl(),
            'error_url' => 'http://localhost:8888/lightfulweb/shopware6/public/account/order/edit/'
                .$transaction->getOrder()->getId()
                .'?error-code=CHECKOUT__ASYNC_PAYMENT_PROCESS_INTERRUPTED', // TODO edit this temp url
        ]);

        // Do some API Call to your payment provider
        $headers = [
            'Authorization' => 'Basic NzBhYmQ1OTQwODQ3ODdhMzkyZTg6NGE2NmI5MWU5YjVjOTBjYTQ3YjA=',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $options = [
            'form_params' => $requestParameters
        ];

        $request = new Request('POST', $this->configReader->getAPIHostName().'/rest/payment', $headers);
        $response = $this->client->send($request, $options);
        // TODO check error_code = 0, means success and get transaction_id
        // TODO log error codes and related messages to log file
        if ($response->getStatusCode() == 200) { // TODO almost all responses are 200 code, so check and handle them accordingly
            // store payment transaction_id to order transaction custom fields
            $transaction->getOrderTransaction()->setCustomFields([
                'betterPaymentTransactionID' => json_decode((string) $response->getBody())->transaction_id
            ]);

            return json_decode((string) $response->getBody())->action_data->url;
        }
        else {
            throw new \RuntimeException($response->getBody()); // TODO improve exception handling
        }
    }
}