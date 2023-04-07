<?php declare(strict_types=1);

namespace BetterPayment\Util;


use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class OrderParametersReader
{
	private SystemConfigService $systemConfigService;
	private EntityRepositoryInterface $orderAddressRepository;
	private EntityRepositoryInterface $customerAddressRepository;
    private EntityRepositoryInterface $languageRepository;
	private EntityRepositoryInterface $currencyRepository;

	public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $orderAddressRepository,
        EntityRepositoryInterface $customerAddressRepository,
        EntityRepositoryInterface $languageRepository,
	    EntityRepositoryInterface $currencyRepository
    ){
        $this->systemConfigService = $systemConfigService;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->languageRepository = $languageRepository;
		$this->currencyRepository = $currencyRepository;
	}

    public function getAllParameters(SyncPaymentTransactionStruct $transaction): array
    {
        $orderTransaction = $transaction->getOrderTransaction();
        $order = $transaction->getOrder();

        return array_merge(
            $this->getCommonParameters($order, $orderTransaction),
            $this->getBillingAddressParameters($order),
            $this->getShippingAddressParameters($order),
            $this->getCompanyDetailParameters($order)
        );
    }

    public function getCommonParameters(OrderEntity $order, OrderTransactionEntity $orderTransaction): array
    {
        $criteria = new Criteria([$order->getOrderCustomer()->getCustomer()->getLanguageId()]);
        $criteria->addAssociation('locale');

        /** @var LanguageEntity $language */
        $language = $this->languageRepository->search(
            $criteria,
            Context::createDefaultContext()
        )->first();

	    $criteria = new Criteria([$order->getCurrencyId()]);

	    /** @var CurrencyEntity $currency */
	    $currency = $this->currencyRepository->search(
		    $criteria,
		    Context::createDefaultContext()
	    )->first();

        return [
            // Any alphanumeric string to identify the Merchant’s order.
            'order_id' => $order->getOrderNumber(),
            // See details about merchant reference - https://testdashboard.betterpayment.de/docs/#merchant-reference
            'merchant_reference' => $order->getOrderNumber().' - '.$this->systemConfigService->getString('core.basicInformation.shopName', $order->getSalesChannelId()),
            // Including possible shipping costs and VAT (float number)
            'amount' => $order->getAmountTotal(),
            // Should be set if the order includes any shipping costs (float number)
            'shipping_costs' => $order->getShippingTotal(),
            // VAT amount (float number) if known
            'vat' => $orderTransaction->getAmount()->getCalculatedTaxes()->getAmount(),
            // 3-letter currency code (ISO 4217). Defaults to ‘EUR’
            'currency' => $currency->getIsoCode(),
            // If the order includes a risk check, this field can be set to prevent customers from making multiple order attempts with different personal information.
            'customer_ip' => $order->getOrderCustomer()->getRemoteAddress(),
            // The language of payment forms in Credit Card and Paypal. Possible locale values - https://testdashboard.betterpayment.de/docs/#locales
            // use substr to convert en-GB to en
            'locale' => substr($language->getLocale()->getCode(), 0, 2)
        ];
    }

    // Billing information is required in all payment methods.
    public function getBillingAddressParameters(OrderEntity $order): array
    {
        $criteria = new Criteria([$order->getBillingAddressId()]);
        $criteria->addAssociations(['country', 'countryState']);

        /** @var OrderAddressEntity $billingAddress */
        $billingAddress = $this->orderAddressRepository->search(
            $criteria,
            Context::createDefaultContext()
        )->first();

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
            'state' => $billingAddress->getCountryState() ? $billingAddress->getCountryState()->getName() : null,
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
            'shipping_state' => $shippingAddress->getCountryState() ? $shippingAddress->getCountryState()->getName() : null,
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
	    $criteria = new Criteria([$order->getBillingAddressId()]);

	    /** @var OrderAddressEntity $billingAddress */
	    $billingAddress = $this->orderAddressRepository->search(
		    $criteria,
		    Context::createDefaultContext()
	    )->first();

        // Get company name from billing address, and fallback to customer's company
        $company = $billingAddress->getCompany();
        if (!$company) {
            $company = $order->getOrderCustomer()->getCompany();
        }

        // Get VAT ID from billing address, and fallback to customer's VAT ID
        $vatId = $billingAddress->getVatId();
        if (!$vatId) {
            $vatId = $order->getOrderCustomer()->getVatIds()[0];
        }

        return [
            // Company name
            'company' => $company,
            // Starts with ISO 3166-1 alpha2 followed by 2 to 11 characters. See more details about Vat - http://ec.europa.eu/taxation_customs/vies/
            'company_vat_id' => $vatId,
        ];
    }
}
