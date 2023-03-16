<?php declare(strict_types=1);

namespace BetterPayment\Util;

use BetterPayment\Installer\CustomFieldInstaller;
use BetterPayment\Installer\PaymentMethodInstaller;
use BetterPayment\PaymentMethod\Invoice;
use BetterPayment\PaymentMethod\PaymentMethod;
use BetterPayment\PaymentMethod\SEPADirectDebit;
use BetterPayment\PaymentMethod\SEPADirectDebitB2B;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use RuntimeException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
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

    public function __construct(
        ConfigReader $configReader,
        OrderParametersReader $orderParametersReader,
        EntityRepositoryInterface $orderTransactionRepository
    ){
        $this->configReader = $configReader;
        $this->orderParametersReader = $orderParametersReader;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    private function getClient(): Client
    {
        return new Client([
            'base_uri' => $this->configReader->getAPIUrl()
        ]);
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Basic '.base64_encode($this->configReader->getAPIKey().':'.$this->configReader->getOutgoingKey()),
            'Content-Type' => 'application/json'
        ];
    }

    public function request(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag = null)
    {
        $requestParameters = $this->getRequestParameters($transaction, $dataBag);
        $body = json_encode($requestParameters);
        $request = new Request('POST', 'rest/payment', $this->getHeaders(), $body);
        try {
            $response = $this->getClient()->send($request);
            $responseBody = json_decode((string) $response->getBody());
            if ($responseBody->error_code == 0) {
                $this->storeBetterPaymentTransactionID($transaction->getOrderTransaction(), $responseBody->transaction_id);
                return $responseBody;
            }
            else {
                throw new RuntimeException('Better Payment Client ERROR: ' . $response->getBody());
            }
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Better Payment Client ERROR: ' . $exception->getMessage());
        }
    }

    private function getRequestParameters(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag = null): array
    {
        $requestParameters = $this->orderParametersReader->getAllParameters($transaction);

        $requestParameters += $this->getPaymentMethodSpecificParameters($transaction, $dataBag);

        $requestParameters += $this->getRiskCheckParameters($transaction);

        // Common parameters for ALL requests.
        $requestParameters += [
            'payment_type' => $transaction->getOrderTransaction()->getPaymentMethod()->getCustomFields()['shortname'],
            'risk_check_approval' => '1',
            'postback_url' => EnvironmentHelper::getVariable('APP_URL').'/api/betterpayment/webhook',
        ];

        if (get_class($transaction) == AsyncPaymentTransactionStruct::class) {
            $requestParameters += [
                'success_url' => $transaction->getReturnUrl(),
                'error_url' => EnvironmentHelper::getVariable('APP_URL').'/account/order/edit/' .$transaction->getOrder()->getId()
                    .'?error-code=CHECKOUT__ASYNC_PAYMENT_PROCESS_INTERRUPTED',
            ];
        }

        return $requestParameters;
    }

    private function storeBetterPaymentTransactionID(OrderTransactionEntity $orderTransactionEntity, string $betterPaymentTransactionID): void
    {
        $this->orderTransactionRepository->update([
            [
                'id' => $orderTransactionEntity->getId(),
                'customFields' => [
                    'better_payment_transaction_id' => $betterPaymentTransactionID
                ]
            ]
        ], Context::createDefaultContext());
    }

    private function getPaymentMethodSpecificParameters(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag = null): array
    {
        $paymentMethodId = $transaction->getOrderTransaction()->getPaymentMethodId();

        switch ($paymentMethodId) {
            case SEPADirectDebit::UUID:
            case SEPADirectDebitB2B::UUID:
                return [
                    'account_holder' => $dataBag->get('betterpayment_account_holder'),
                    'iban' => $dataBag->get('betterpayment_iban'),
                    'bic' => $dataBag->get('betterpayment_bic'),
                    'sepa_mandate' => $dataBag->get('betterpayment_sepa_mandate')
                ];
            default:
                return [];
        }
    }

    private function getRiskCheckParameters(SyncPaymentTransactionStruct $transaction): array
    {
        $params = [];

        $paymentMethodId = $transaction->getOrderTransaction()->getPaymentMethodId();
        $customer = $transaction->getOrder()->getOrderCustomer()->getCustomer();

        switch ($paymentMethodId) {
            case SEPADirectDebit::UUID:
                if ($this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_DATE_OF_BIRTH)) {
                    $params += [
                        'date_of_birth' => $this->getBirthday($customer)
                    ];
                }
                if ($this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_GENDER)) {
                    $params += [
                        'gender' => $this->getGender($customer)
                    ];
                }
                break;
            case Invoice::UUID:
                if ($this->configReader->getBool(ConfigReader::INVOICE_COLLECT_DATE_OF_BIRTH)) {
                    $params += [
                        'date_of_birth' => $this->getBirthday($customer)
                    ];
                }
                if ($this->configReader->getBool(ConfigReader::INVOICE_COLLECT_GENDER)) {
                    $params += [
                        'gender' => $this->getGender($customer)
                    ];
                }
                break;
        }

        return $params;
    }

    private function getBirthday(CustomerEntity $customer): ?string
    {
        return $customer->getBirthday() ? $customer->getBirthday()->format('Y-m-d') : null;
    }
    
    // returns m|f|d|null as required by API and as custom field setup (null if not set yet)
    private function getGender(CustomerEntity $customer): ?string
    {
        
        return $customer->getCustomFields()[CustomFieldInstaller::CUSTOMER_GENDER];
    }

    public function getBetterPaymentTransaction(string $id): array
    {
        $request = new Request('GET', 'rest/transactions/'.$id, $this->getHeaders());
        try {
            $response = $this->getClient()->send($request);
            $responseBody = json_decode((string) $response->getBody());
            if (!$responseBody->error_code) {
                return $responseBody;
            }
            else {
                throw new RuntimeException('Better Payment Client ERROR: ' . $response->getBody());
            }
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Better Payment Client ERROR: ' . $exception->getMessage());
        }
    }
}