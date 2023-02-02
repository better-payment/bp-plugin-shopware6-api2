<?php

namespace BetterPayment\Util;

use BetterPayment\PaymentMethod\SEPADirectDebit;
use BetterPayment\PaymentMethod\SEPADirectDebitB2B;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use RuntimeException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

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
        $this->client = new Client([
            'base_uri' => $this->configReader->getAPIHostName()
        ]);
    }

    public function request(AsyncPaymentTransactionStruct $transaction, string $paymentType): string
    {
        $headers = [
            'Authorization' => 'Basic '.base64_encode($this->configReader->getAPIKey().':'.$this->configReader->getOutgoingKey()),
            'Content-Type' => 'application/json'
        ];

        $orderParameters = $this->orderParametersReader->getAllParameters($transaction);
        $requestParameters = array_merge($orderParameters, [
            'payment_type' => $paymentType,
            'risk_check_approval' => '1',
            'postback_url' => EnvironmentHelper::getVariable('APP_URL').'/api/betterpayment/webhook',
            'success_url' => $transaction->getReturnUrl(),
            'error_url' => EnvironmentHelper::getVariable('APP_URL').'/account/order/edit/' .$transaction->getOrder()->getId()
                .'?error-code=CHECKOUT__ASYNC_PAYMENT_PROCESS_INTERRUPTED',
        ]);
        $body = json_encode($requestParameters);

        $request = new Request('POST', 'rest/payment', $headers, $body);
        try {
            $response = $this->client->send($request);
            $responseBody = json_decode((string) $response->getBody());
            if ($responseBody->error_code == 0) {
                // store payment transaction_id to order transaction custom fields
                $this->orderTransactionRepository->update([
                    [
                        'id' => $transaction->getOrderTransaction()->getId(),
                        'customFields' => [
                            'better_payment_transaction_id' => $responseBody->transaction_id
                        ]
                    ]
                ], Context::createDefaultContext());

                return $responseBody->action_data->url;
            }
            else {
                throw new RuntimeException('Better Payment Client ERROR: ' . $response->getBody());
            }
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Better Payment Client ERROR: ' . $exception->getMessage());
        }
    }

    public function syncRequest(SyncPaymentTransactionStruct $transaction, string $paymentType, RequestDataBag $dataBag)
    {
        $headers = [
            'Authorization' => 'Basic '.base64_encode($this->configReader->getAPIKey().':'.$this->configReader->getOutgoingKey()),
            'Content-Type' => 'application/json'
        ];

        $orderParameters = $this->orderParametersReader->getAllParameters($transaction);
        if ($paymentType == SEPADirectDebit::SHORTNAME) {
            $orderParameters += [
                'account_holder' => $dataBag->get('betterpayment_account_holder'),
                'iban' => $dataBag->get('betterpayment_iban'),
                'bic' => $dataBag->get('betterpayment_bic'),
                'sepa_mandate' => $dataBag->get('betterpayment_sepa_mandate')
            ];
        }
        $requestParameters = array_merge($orderParameters, [
            'payment_type' => $paymentType,
            'risk_check_approval' => '1',
            'postback_url' => EnvironmentHelper::getVariable('APP_URL').'/api/betterpayment/webhook',
//            'success_url' => $transaction->getReturnUrl(),
//            'error_url' => EnvironmentHelper::getVariable('APP_URL').'/account/order/edit/' .$transaction->getOrder()->getId()
//                .'?error-code=CHECKOUT__ASYNC_PAYMENT_PROCESS_INTERRUPTED',
        ]);
        $body = json_encode($requestParameters);

        $request = new Request('POST', 'rest/payment', $headers, $body);
        try {
            $response = $this->client->send($request);
            $responseBody = json_decode((string) $response->getBody());
            if ($responseBody->error_code == 0) {
                // store payment transaction_id to order transaction custom fields
                $this->orderTransactionRepository->update([
                    [
                        'id' => $transaction->getOrderTransaction()->getId(),
                        'customFields' => [
                            'better_payment_transaction_id' => $responseBody->transaction_id
                        ]
                    ]
                ], Context::createDefaultContext());

                return $responseBody->status;
            }
            else {
                throw new RuntimeException('Better Payment Client ERROR: ' . $response->getBody());
            }
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Better Payment Client ERROR: ' . $exception->getMessage());
        }
    }
}