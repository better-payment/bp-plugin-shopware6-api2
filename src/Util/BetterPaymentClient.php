<?php declare(strict_types=1);

namespace BetterPayment\Util;

use BetterPayment\Installer\PaymentMethodInstaller;
use BetterPayment\PaymentMethod\Invoice;
use BetterPayment\PaymentMethod\InvoiceB2B;
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

    public function request(SyncPaymentTransactionStruct $transaction, string $paymentType, RequestDataBag $dataBag = null)
    {
        $requestParameters = $this->getRequestParameters($transaction, $paymentType, $dataBag);
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

    private function getClient(): Client
    {
        return new Client([
            'base_uri' => $this->configReader->getAPIHostName()
        ]);
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Basic '.base64_encode($this->configReader->getAPIKey().':'.$this->configReader->getOutgoingKey()),
            'Content-Type' => 'application/json'
        ];
    }

    // TODO refactor this method
    private function getRequestParameters(SyncPaymentTransactionStruct $transaction, string $paymentType, RequestDataBag $dataBag = null): array
    {
        // this is common for almost all payment methods
        $requestParameters = $this->orderParametersReader->getAllParameters($transaction);

        // this is specific to Direct Debit payment methods
        if ($paymentType == SEPADirectDebit::SHORTNAME || $paymentType == SEPADirectDebitB2B::SHORTNAME) {
            $requestParameters += [
                'account_holder' => $dataBag->get('betterpayment_account_holder'),
                'iban' => $dataBag->get('betterpayment_iban'),
                'bic' => $dataBag->get('betterpayment_bic'),
                'sepa_mandate' => $dataBag->get('betterpayment_sepa_mandate')
            ];
        }

        // this is specific to following payment methods
        if ($paymentType == Invoice::SHORTNAME || $paymentType == InvoiceB2B::SHORTNAME
            || $paymentType == SEPADirectDebit::SHORTNAME || $paymentType == SEPADirectDebitB2B::SHORTNAME)
        {
            $customer = $transaction->getOrder()->getOrderCustomer()->getCustomer();
            $requestParameters += $this->riskCheckParameters($paymentType, $customer);
        }

        // this is common for all payment methods
        $requestParameters += [
            'payment_type' => $paymentType,
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

    // store better payment transaction_id in order transaction custom fields
    private function storeBetterPaymentTransactionID(OrderTransactionEntity $orderTransactionEntity, string $betterPaymentTransactionID): void
    {
        // TODO once again make sure below line is not working
//        $orderTransactionEntity->setCustomFields();

        $this->orderTransactionRepository->update([
            [
                'id' => $orderTransactionEntity->getId(),
                'customFields' => [
                    'better_payment_transaction_id' => $betterPaymentTransactionID
                ]
            ]
        ], Context::createDefaultContext());
    }

    // TODO this can be used to remove $paymentType parameter dependency from request() method
    private function getPaymentMethodByTransaction(SyncPaymentTransactionStruct $transaction): ?PaymentMethod
    {
        $handler = $transaction->getOrderTransaction()->getPaymentMethod()->getHandlerIdentifier();
        foreach (PaymentMethodInstaller::PAYMENT_METHODS as $PAYMENT_METHOD) {
            /** @var PaymentMethod $paymentMethod */
            $paymentMethod = new $PAYMENT_METHOD();
            if ($paymentMethod->getHandler() == $handler)
                return $paymentMethod;
        }

        return null;
    }

    // TODO this can be used to generalise preparing payment method specific request parameters
    private function paymentMethodParameters(PaymentMethod $paymentMethod): array
    {
        // TODO make dataBag class variable to fetch it here as $this->dataBag
        $dataBag = new RequestDataBag();
        switch ($paymentMethod) {
            case SEPADirectDebit::class:
            case SEPADirectDebitB2B::class:
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

    private function riskCheckParameters(string $paymentType, CustomerEntity $customer): array
    {
        $params = [];

        // depending on payment method(type) and config option retrieve birthdate
        if (($paymentType == SEPADirectDebit::SHORTNAME && $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_DATE_OF_BIRTH))
            || ($paymentType == SEPADirectDebitB2B::SHORTNAME && $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_B2B_COLLECT_DATE_OF_BIRTH))
            || ($paymentType == Invoice::SHORTNAME && $this->configReader->getBool(ConfigReader::INVOICE_COLLECT_DATE_OF_BIRTH))
            || ($paymentType == InvoiceB2B::SHORTNAME && $this->configReader->getBool(ConfigReader::INVOICE_B2B_COLLECT_DATE_OF_BIRTH)))
        {
            $params += [
                'date_of_birth' => $this->getBirthday($customer)
            ];
        }

        // depending on payment method(type) and config option retrieve gender
        if (($paymentType == SEPADirectDebit::SHORTNAME && $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_GENDER))
            || ($paymentType == SEPADirectDebitB2B::SHORTNAME && $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_B2B_COLLECT_GENDER))
            || ($paymentType == Invoice::SHORTNAME && $this->configReader->getBool(ConfigReader::INVOICE_COLLECT_GENDER))
            || ($paymentType == InvoiceB2B::SHORTNAME && $this->configReader->getBool(ConfigReader::INVOICE_B2B_COLLECT_GENDER)))
        {
            $params += [
                'gender' => $this->getGender($customer)
            ];
        }

        return $params;
    }

    // birthdate field must be activated in shopware
    private function getBirthday(CustomerEntity $customer): ?string
    {
        return $customer->getBirthday() ? $customer->getBirthday()->format('Y-m-d') : null;
    }

    // custom field is used to determine customer gender
    private function getGender(CustomerEntity $customer): ?string
    {
        // returns m|f|d|null as required by API and as custom field setup (null if not set yet)
        return $customer->getCustomFields()['better_payment_gender'];
    }
}