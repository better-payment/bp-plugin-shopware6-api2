<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;

use BetterPayment\PaymentHandler\InvoiceB2BHandler;
use BetterPayment\PaymentHandler\InvoiceHandler;
use BetterPayment\PaymentHandler\SEPADirectDebitB2BHandler;
use BetterPayment\PaymentHandler\SEPADirectDebitHandler;
use BetterPayment\Storefront\Struct\CheckoutData;
use BetterPayment\Util\ConfigReader;
use Shopware\Core\Framework\Uuid\Uuid;
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


        if ($paymentMethod->getHandlerIdentifier() == SEPADirectDebitHandler::class) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/sepa-direct-debit.html.twig',
                'creditorID' => $this->configReader->getString(ConfigReader::SEPA_DIRECT_DEBIT_CREDITOR_ID),
                'companyName' => $this->configReader->getString(ConfigReader::SEPA_DIRECT_DEBIT_COMPANY_NAME),
                'mandateReference' => Uuid::randomHex(),
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
        elseif ($paymentMethod->getHandlerIdentifier() == SEPADirectDebitB2BHandler::class) {
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
        elseif ($paymentMethod->getHandlerIdentifier() == InvoiceHandler::class && $this->configReader->getBool(ConfigReader::INVOICE_RISK_CHECK_AGREEMENT)) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/invoice.html.twig',
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
        elseif ($paymentMethod->getHandlerIdentifier() == InvoiceB2BHandler::class && $this->configReader->getBool(ConfigReader::INVOICE_B2B_RISK_CHECK_AGREEMENT)) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/invoice-b2b.html.twig',
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
    }
}