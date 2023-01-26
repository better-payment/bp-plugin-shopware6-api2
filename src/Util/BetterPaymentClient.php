<?php

namespace BetterPayment\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class BetterPaymentClient
{
    private ConfigReader $configReader;
    private OrderParametersReader $orderParametersReader;
    private EntityRepositoryInterface $orderTransactionRepository;
    private Client $client;

    public function __construct(
        ConfigReader $configReader,
        OrderParametersReader $orderParametersReader,
        EntityRepositoryInterface $orderTransactionRepository
    ){
        $this->configReader = $configReader;
        $this->orderParametersReader = $orderParametersReader;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->client = new Client();
    }

    public function request(AsyncPaymentTransactionStruct $transaction, string $paymentType): string
    {
        $orderParameters = $this->orderParametersReader->getAllParameters($transaction);
        $requestParameters = array_merge($orderParameters, [
            'payment_type' => $paymentType,
            'risk_check_approval' => '1',
            'postback_url' => EnvironmentHelper::getVariable('APP_URL').'/api/betterpayment/webhook',
            'success_url' => $transaction->getReturnUrl(),
            'error_url' => EnvironmentHelper::getVariable('APP_URL').'/account/order/edit/' .$transaction->getOrder()->getId()
                .'?error-code=CHECKOUT__ASYNC_PAYMENT_PROCESS_INTERRUPTED',
        ]);

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
            $this->orderTransactionRepository->update([
                [
                    'id' => $transaction->getOrderTransaction()->getId(),
                    'customFields' => [
                        'better_payment_transaction_id' => json_decode((string) $response->getBody())->transaction_id
                    ]
                ]
            ], Context::createDefaultContext());

            return json_decode((string) $response->getBody())->action_data->url;
        }
        else {
            throw new \RuntimeException($response->getBody()); // TODO improve exception handling
        }
    }
}