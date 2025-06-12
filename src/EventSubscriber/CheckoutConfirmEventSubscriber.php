<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;

use BetterPayment\Installer\CustomFieldInstaller;
use BetterPayment\PaymentMethod\ApplePay;
use BetterPayment\PaymentMethod\GooglePay;
use BetterPayment\PaymentMethod\Invoice;
use BetterPayment\PaymentMethod\InvoiceB2B;
use BetterPayment\PaymentMethod\SEPADirectDebit;
use BetterPayment\PaymentMethod\SEPADirectDebitB2B;
use BetterPayment\Storefront\Struct\CheckoutData;
use BetterPayment\Util\ConfigReader;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmEventSubscriber implements EventSubscriberInterface
{
    private ConfigReader $configReader;

    public function __construct(ConfigReader $configReader)
    {
        $this->configReader = $configReader;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'addPaymentMethodSpecificFormFields',
            AccountEditOrderPageLoadedEvent::class => 'addPaymentMethodSpecificFormFields'
        ];
    }

    public function addPaymentMethodSpecificFormFields(PageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        $customer = $event->getSalesChannelContext()->getCustomer();
        if ($paymentMethod->getId() == SEPADirectDebit::UUID) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/sepa-direct-debit.html.twig',
                'creditorID' => $this->configReader->getString(ConfigReader::SEPA_DIRECT_DEBIT_CREDITOR_ID),
                'companyName' => $this->configReader->getString(ConfigReader::SEPA_DIRECT_DEBIT_COMPANY_NAME),
                'mandateReference' => Uuid::randomHex(),
                'birthdayIsMissing' => $this->birthdayIsMissing($customer) && $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_DATE_OF_BIRTH),
                'genderIsMissing' => $this->genderIsMissing($customer) && $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_GENDER),
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
        elseif ($paymentMethod->getId() == SEPADirectDebitB2B::UUID) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/sepa-direct-debit-b2b.html.twig',
                'creditorID' => $this->configReader->getString(ConfigReader::SEPA_DIRECT_DEBIT_B2B_CREDITOR_ID),
                'companyName' => $this->configReader->getString(ConfigReader::SEPA_DIRECT_DEBIT_B2B_COMPANY_NAME),
                'mandateReference' => Uuid::randomHex(),
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
        
        // in invoice payment methods (b2c|b2b) only risk check agreement checkbox is added as form field when corresponding config is enabled
        // that's why it also needs to check in if condition whether config is enabled before assigning related template view
        elseif ($paymentMethod->getId() == Invoice::UUID) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/invoice.html.twig',
                'birthdayIsMissing' => $this->birthdayIsMissing($customer) && $this->configReader->getBool(ConfigReader::INVOICE_COLLECT_DATE_OF_BIRTH),
                'genderIsMissing' => $this->genderIsMissing($customer) && $this->configReader->getBool(ConfigReader::INVOICE_COLLECT_GENDER),
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
        elseif ($paymentMethod->getId() == InvoiceB2B::UUID) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/invoice-b2b.html.twig',
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
        elseif ($paymentMethod->getId() == ApplePay::UUID) {
            $data = new CheckoutData();
            $data->assign([
                'template' => '@Storefront/betterpayment/apple-pay.html.twig',
                'initialData' => [
                    ...$this->getExpressPaymentMethodInitialData($event),
                    'applePay' => [
                        'merchantCapabilities' => $this->configReader->getBool(ConfigReader::APPLE_PAY_3DS_ENABLED) ? ["supports3DS"] : [],
                        'supportedNetworks' => $this->configReader->get(ConfigReader::APPLE_PAY_SUPPORTED_NETWORKS),
                    ],
                ],
            ]);
            $page->addExtension('expressPaymentMethod', $data);
        }
        elseif ($paymentMethod->getId() == GooglePay::UUID) {
            $data = new CheckoutData();
            $data->assign([
                'template' => '@Storefront/betterpayment/google-pay.html.twig',
                'initialData' => [
                    ...$this->getExpressPaymentMethodInitialData($event),
                    'googlePay' => [
                        'allowedCardNetworks' => $this->configReader->get(ConfigReader::GOOGLE_PAY_ALLOWED_CARD_NETWORKS),
                        'allowedAuthMethods' => $this->configReader->get(ConfigReader::GOOGLE_PAY_ALLOWED_AUTH_METHODS),
                        'gateway' => 'processingpagateq',
                        'gatewayMerchantId' => '7209700000',
                        'merchantId' => 'BCR2DN4TWWK67WTY',
                        'merchantName'=>'Demo Merchant',
                    ],
                ],
            ]);
            $page->addExtension('expressPaymentMethod', $data);
        }
    }

    private function getExpressPaymentMethodInitialData(PageLoadedEvent $event): array
    {
        $page = $event->getPage();
        $customer = $event->getSalesChannelContext()->getCustomer();

        return [
            'orderId' => Uuid::randomHex(),
            'email' => $customer->getEmail(),
            'shippingCosts' => $page instanceof AccountEditOrderPage
                ? $page->getOrder()->getShippingTotal()
                : $page->getCart()->getShippingCosts()->getTotalPrice(),
            'vat' => $page instanceof AccountEditOrderPage
                ? $page->getOrder()->getPrice()->getCalculatedTaxes()->getAmount()
                : $page->getCart()->getPrice()->getCalculatedTaxes()->getAmount(),
            'countryCode' => $customer->getDefaultBillingAddress()->getCountry()->getIso(),
            'currency' => $event->getSalesChannelContext()->getCurrency()->getIsoCode(),
            'shopName' => $this->configReader->getSystemConfig('core.basicInformation.shopName', $event->getSalesChannelContext()->getSalesChannelId()),
            'customerId' => $customer->getCustomerNumber(),
            'customerIp' => $customer->getRemoteAddress(),
            'postbackUrl' => $this->configReader->getPostbackUrl(),
            'appName' => $this->configReader->getAppName(),
            'appVersion' => $this->configReader->getAppVersion(),
        ];
    }

    private function birthdayIsMissing(CustomerEntity $customer): bool
    {
        return !$customer->getBirthday();
    }

    private function genderIsMissing(CustomerEntity $customer): bool
    {
        $customFields = $customer->getCustomFields();
        return isset($customFields[CustomFieldInstaller::CUSTOMER_GENDER]) ? !$customFields[CustomFieldInstaller::CUSTOMER_GENDER] : true;
    }
}
