<?php declare(strict_types=1);

namespace BetterPayment\Util;


use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class OrderParametersReader
{
    private EntityRepositoryInterface $customerAddressRepository;

    // TODO check whether parent object is not null before accessing child object
    public function __construct(EntityRepositoryInterface $customerAddressRepository)
    {
        $this->customerAddressRepository = $customerAddressRepository;
    }

    public function getAllParameters(SyncPaymentTransactionStruct $transaction): array
    {
        $order = $transaction->getOrder();

        return array_merge(
            $this->getCommonParameters($order),
            $this->getBillingAddressParameters($order),
            $this->getShippingAddressParameters($order),
            $this->getCompanyDetailParameters($order)
        );
    }

    // The following parameters are used with all payment methods
    public function getCommonParameters(OrderEntity $order): array
    {
        return [
            // Any alphanumeric string to identify the Merchant’s order.
            'order_id' => $order->getId(),
            // See details about merchant reference - https://testdashboard.betterpayment.de/docs/#merchant-reference
            'merchant_reference' => $order->getId().' - '.'SHOP NAME HERE', // TODO fetch shop name or sales channel name https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/dependency-injection
            // Including possible shipping costs and VAT (float number)
            'amount' => $order->getAmountTotal(), // TODO needs to be checked
            // Should be set if the order includes any shipping costs (float number)
            'shipping_costs' => $order->getShippingTotal(), // TODO needs to be checked
            // VAT amount (float number) if known
            'VAT' => $order->getAmountTotal() - $order->getAmountNet(), // TODO needs to be checked
            // 3-letter currency code (ISO 4217). Defaults to ‘EUR’
            'currency' => $order->getCurrency()->getIsoCode(),
            // If the order includes a risk check, this field can be set to prevent customers from making multiple order attempts with different personal information.
            'customer_ip' => $order->getOrderCustomer()->getRemoteAddress(),
            // https://testdashboard.betterpayment.de/docs/#original-transaction-id
//            'original_transaction_id' => '',
            // The language of payment forms in Credit Card and Paypal. Possible locale values - https://testdashboard.betterpayment.de/docs/#locales
            'locale' => $order->getLanguage()->getLocale() ? $order->getLanguage()->getLocale()->getCode() : null
        ];
    }

    // Billing information is required in all payment methods.
    public function getBillingAddressParameters(OrderEntity $order): array
    {
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
            'state' => $billingAddress->getCountryState() ? $billingAddress->getCountryState()->getName() : null, //TODO check it again
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
    public function getShippingAddressParameters(OrderEntity $order): array
    {
        $criteria = new Criteria([$order->getOrderCustomer()->getCustomer()->getDefaultShippingAddressId()]);
        $criteria->addAssociations(['country', 'countryState']);

        /** @var CustomerAddressEntity $shippingAddress */
        $shippingAddress = $this->customerAddressRepository->search(
            $criteria,
            Context::createDefaultContext()
        )->first();

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
            'shipping_state' => $shippingAddress->getCountryState() ? $shippingAddress->getCountryState()->getName() : null, //TODO check it again
            // Country Code in ISO 3166-1 alpha2
            'shipping_country' => $shippingAddress->getCountry()->getIso(),
            // Customer’s first name
            'shipping_first_name' => $shippingAddress->getFirstName(),
            // Customer’s last name
            'shipping_last_name' => $shippingAddress->getLastName(),
        ];
    }

    // Company details are required in B2B Invoice and B2B SEPA Direct Debit orders.
    public function getCompanyDetailParameters(OrderEntity $order): array
    {
        return [
            // Company name
            'company' => $order->getBillingAddress()->getCompany(), // TODO get company name from shipping maybe ?
            // Starts with ISO 3166-1 alpha2 followed by 2 to 11 characters. See more details about Vat - http://ec.europa.eu/taxation_customs/vies/
            'company_vat_id' => '',
            // Company trade registry no
            'company_trade_register' => ''
        ];
    }
}