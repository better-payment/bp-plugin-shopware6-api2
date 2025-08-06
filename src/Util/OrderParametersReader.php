<?php declare(strict_types=1);

namespace BetterPayment\Util;

use BetterPayment\Installer\CustomFieldInstaller;
use BetterPayment\PaymentHandler\AsynchronousBetterPaymentHandler;
use BetterPayment\PaymentMethod\Invoice;
use BetterPayment\PaymentMethod\InvoiceB2B;
use BetterPayment\PaymentMethod\SEPADirectDebit;
use BetterPayment\PaymentMethod\SEPADirectDebitB2B;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\Request;

class OrderParametersReader
{
    private ConfigReader $configReader;
    private EntityRepository $orderTransactionRepository;

    public function __construct(
        ConfigReader $configReader,
        EntityRepository $orderTransactionRepository,
    ) {
        $this->configReader = $configReader;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    public function getAllParameters(Request $request, PaymentTransactionStruct $transaction, Context $context): array
    {
        $criteria = new Criteria([$transaction->getOrderTransactionId()]);
        $criteria->addAssociations([
            'paymentMethod',
            'order',
            'order.currency',
            'order.language.locale',
            'order.orderCustomer.customer',
            'order.billingAddress',
            'order.billingAddress.country',
            'order.billingAddress.countryState',
            'order.deliveries.shippingOrderAddress',
            'order.deliveries.shippingOrderAddress.country',
            'order.deliveries.shippingOrderAddress.countryState',
        ]);

        /* @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        return array_merge(
            $this->getCommonParameters($orderTransaction),
            $this->getBillingAddressParameters($orderTransaction),
            $this->getShippingAddressParameters($orderTransaction),
            $this->getRiskCheckParameters($orderTransaction),
            $this->getCompanyDetailParameters($orderTransaction),
            $this->getRedirectUrlParameters($orderTransaction, $transaction),
            $this->getSpecialParameters($orderTransaction, $request),
        );
    }

    private function getCommonParameters(OrderTransactionEntity $orderTransaction): array
    {
        $order = $orderTransaction->getOrder();

        return [
            'payment_type' => $orderTransaction->getPaymentMethod()->getCustomFields()['shortname'],
            // Any alphanumeric string to identify the Merchant’s order.
            'order_id' => $order->getOrderNumber(),
            // Any alphanumeric string to provide the customer number of a Merchant’s order (up to 40 characters) for factoring or debt collection
            'customer_id' => $order->getOrderCustomer()->getCustomerNumber(),
            // See details about merchant reference - https://testdashboard.betterpayment.de/docs/#merchant-reference
            'merchant_reference' => $order->getOrderNumber().' - '.$this->configReader->getSystemConfig('core.basicInformation.shopName', $order->getSalesChannelId()),
            // Including possible shipping costs and VAT (float number)
            'amount' => $order->getAmountTotal(),
            // Should be set if the order includes any shipping costs (float number)
            'shipping_costs' => $order->getShippingTotal(),
            // VAT amount (float number) if known
            'vat' => $orderTransaction->getAmount()->getCalculatedTaxes()->getAmount(),
            // 3-letter currency code (ISO 4217). Defaults to ‘EUR’
            'currency' => $order->getCurrency()->getIsoCode(),
            // If the order includes a risk check, this field can be set to prevent customers from making multiple order attempts with different personal information.
            'customer_ip' => $order->getOrderCustomer()->getRemoteAddress(),
            // The language of payment forms in Credit Card and Paypal. Possible locale values - https://testdashboard.betterpayment.de/docs/#locales
            // use substr to convert en-GB to en
            'locale' => substr($order->getLanguage()->getLocale()->getCode(), 0, 2),
            'postback_url' => $this->configReader->getPostbackUrl(),
            'app_name' => $this->configReader->getAppName(),
            'app_version' => $this->configReader->getAppVersion(),
            'risk_check_approval' => '1',
        ];
    }

    // Billing information is required in all payment methods.
    private function getBillingAddressParameters(OrderTransactionEntity $orderTransaction): array
    {
        $order = $orderTransaction->getOrder();
        $billingAddress = $order->getBillingAddress();

        return [
            // Street address
            'address' => $billingAddress->getStreet(),
            // Second address line
            'address2' => $billingAddress->getAdditionalAddressLine1(),
            // The town, district or city of the billing address
            'city' => $billingAddress->getCity(),
            // The postal code or zip code of the billing address
            'postal_code' => $billingAddress->getZipcode(),
            // The county, state or region of the billing address
            'state' => $billingAddress->getCountryState()?->getName(),
            // Country Code in ISO 3166-1
            'country' => $billingAddress->getCountry()->getIso(),
            // Customer’s first name
            'first_name' => $billingAddress->getFirstName(),
            // Customer’s last name
            'last_name' => $billingAddress->getLastName(),
            // Customer’s last email. We suggest to provide an email when transaction's payment method type is CC(credit card) to avoid declines in 3DS2.
            'email' => $order->getOrderCustomer()->getEmail(),
            // Customer’s phone number
            'phone' => $billingAddress->getPhoneNumber(),
        ];
    }

    // Shipping address can be specified when it differs from the billing address.
    private function getShippingAddressParameters(OrderTransactionEntity $orderTransaction): array
    {
        // TODO: check $order->getDeliveries()->first() null case, with fallback to customer's defaultShippingAddress
        $shippingAddress = $orderTransaction->getOrder()->getDeliveries()->first()->getShippingOrderAddress();

        return [
            // Street address
            'shipping_address' => $shippingAddress->getStreet(),
            // Second address line
            'shipping_address2' => $shippingAddress->getAdditionalAddressLine1(),
            // Name of the company of the given shipping address
            'shipping_company' => $shippingAddress->getCompany(),
            // The town, district or city of the shipping address
            'shipping_city' => $shippingAddress->getCity(),
            // The postal code or zip code of the shipping address
            'shipping_postal_code' => $shippingAddress->getZipcode(),
            // The county, state or region of the shipping address
            'shipping_state' => $shippingAddress->getCountryState()?->getName(),
            // Country Code in ISO 3166-1 alpha2
            'shipping_country' => $shippingAddress->getCountry()->getIso(),
            // Customer’s first name
            'shipping_first_name' => $shippingAddress->getFirstName(),
            // Customer’s last name
            'shipping_last_name' => $shippingAddress->getLastName(),
        ];
    }

    // Company details are required in B2B Invoice and B2B SEPA Direct Debit orders.
    private function getCompanyDetailParameters(OrderTransactionEntity $orderTransaction): array
    {
        if (in_array($orderTransaction->getPaymentMethodId(), [SEPADirectDebitB2B::UUID, InvoiceB2B::UUID])) {
            $order = $orderTransaction->getOrder();
            $billingAddress = $order->getBillingAddress();

            // Get company name from billing address, and fallback to customer's company
            $company = $billingAddress->getCompany();
            if (!$company) {
                $company = $order->getOrderCustomer()->getCompany();
            }

            // Get VAT ID from billing address, and fallback to customer's VAT ID
            $vatId = $billingAddress->getVatId();
            if (!$vatId) {
                $vatId = $order->getOrderCustomer()->getVatIds() ? $order->getOrderCustomer()->getVatIds()[0] : null;
            }

            return [
                // Company name
                'company' => $company,
                // Starts with ISO 3166-1 alpha2 followed by 2 to 11 characters. See more details about Vat - http://ec.europa.eu/taxation_customs/vies/
                'company_vat_id' => $vatId,
            ];
        }

	    return [];
    }

    private function getRedirectUrlParameters(OrderTransactionEntity $orderTransaction, PaymentTransactionStruct $transaction): array
    {
        if ($orderTransaction->getPaymentMethod()->getHandlerIdentifier() == AsynchronousBetterPaymentHandler::class) {
            return [
                'success_url' => $transaction->getReturnUrl(),
                'error_url' => $this->configReader->getAppUrl() . '/account/order/edit/' . $orderTransaction->getOrderId()
                    . '?error-code=CHECKOUT__ASYNC_PAYMENT_PROCESS_INTERRUPTED',
            ];
        }

        return [];
    }

    private function getSpecialParameters(OrderTransactionEntity $orderTransaction, Request $request): array
    {
        if (in_array($orderTransaction->getPaymentMethodId(), [SEPADirectDebit::UUID, SEPADirectDebitB2B::UUID])) {
            return [
                'account_holder' => $request->get('betterpayment_account_holder'),
                'iban' => $request->get('betterpayment_iban'),
                'bic' => $request->get('betterpayment_bic'),
                'sepa_mandate' => $request->get('betterpayment_sepa_mandate')
            ];
        }

        return [];
    }

    private function getRiskCheckParameters(OrderTransactionEntity $orderTransaction): array
    {
        $parameters = [];
        $paymentMethodId = $orderTransaction->getPaymentMethodId();
        $customer = $orderTransaction->getOrder()->getOrderCustomer()->getCustomer();

        if ($paymentMethodId == SEPADirectDebit::UUID) {
            if ($this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_DATE_OF_BIRTH)) {
                $parameters += [
                    'date_of_birth' => $this->getBirthday($customer)
                ];
            }

            if ($this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_GENDER)) {
                $parameters += [
                    'gender' => $this->getGender($customer)
                ];
            }

            return $parameters;
        }

        if ($paymentMethodId == Invoice::UUID) {
            if ($this->configReader->getBool(ConfigReader::INVOICE_COLLECT_DATE_OF_BIRTH)) {
                $parameters += [
                    'date_of_birth' => $this->getBirthday($customer)
                ];
            }

            if ($this->configReader->getBool(ConfigReader::INVOICE_COLLECT_GENDER)) {
                $parameters += [
                    'gender' => $this->getGender($customer)
                ];
            }

            return $parameters;
        }

        return [];
    }

    private function getBirthday(CustomerEntity $customer): ?string
    {
        return $customer->getBirthday()?->format('Y-m-d');
    }

    // returns m|f|d|null as required by API and as custom field setup (null if not set yet)
    private function getGender(CustomerEntity $customer): ?string
    {
        return $customer->getCustomFields()[CustomFieldInstaller::CUSTOMER_GENDER];
    }
}
