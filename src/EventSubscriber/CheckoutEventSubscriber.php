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

class CheckoutEventSubscriber implements EventSubscriberInterface
{
    private ConfigReader $configReader;

    public function __construct(ConfigReader $configReader)
    {
        $this->configReader = $configReader;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'addForm',
            AccountEditOrderPageLoadedEvent::class => 'addForm'
        ];
    }

    public function addForm(PageLoadedEvent $event): void
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
        elseif ($paymentMethod->getHandlerIdentifier() == InvoiceHandler::class && $this->configReader->getBool(ConfigReader::INVOICE_DISPLAY_INSTRUCTION)) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/invoice-instructions.html.twig',
                'iban' => $this->configReader->getString(ConfigReader::INVOICE_IBAN),
                'bic' => $this->configReader->getString(ConfigReader::INVOICE_BIC),
                'orderID' => Uuid::randomHex(),
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
        elseif ($paymentMethod->getHandlerIdentifier() == InvoiceB2BHandler::class && $this->configReader->getBool(ConfigReader::INVOICE_B2B_DISPLAY_INSTRUCTION)) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/invoice-instructions.html.twig',
                'iban' => $this->configReader->getString(ConfigReader::INVOICE_B2B_IBAN),
                'bic' => $this->configReader->getString(ConfigReader::INVOICE_B2B_BIC),
                'orderID' => Uuid::randomHex(),
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
    }
}