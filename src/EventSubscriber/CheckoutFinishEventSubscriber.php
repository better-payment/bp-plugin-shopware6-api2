<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;

use BetterPayment\PaymentHandler\InvoiceB2BHandler;
use BetterPayment\PaymentHandler\InvoiceHandler;
use BetterPayment\Storefront\Struct\CheckoutData;
use BetterPayment\Util\ConfigReader;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutFinishEventSubscriber implements EventSubscriberInterface
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
            CheckoutFinishPageLoadedEvent::class => 'addInstructions',
        ];
    }

    public function addInstructions(CheckoutFinishPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();

        if ($paymentMethod->getHandlerIdentifier() == InvoiceHandler::class && $this->configReader->getBool(ConfigReader::INVOICE_DISPLAY_INSTRUCTION)) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/invoice-instructions.html.twig',
                'iban' => $this->configReader->getString(ConfigReader::INVOICE_IBAN),
                'bic' => $this->configReader->getString(ConfigReader::INVOICE_BIC)
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
        elseif ($paymentMethod->getHandlerIdentifier() == InvoiceB2BHandler::class && $this->configReader->getBool(ConfigReader::INVOICE_B2B_DISPLAY_INSTRUCTION)) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/invoice-instructions.html.twig',
                'iban' => $this->configReader->getString(ConfigReader::INVOICE_B2B_IBAN),
                'bic' => $this->configReader->getString(ConfigReader::INVOICE_B2B_BIC)
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
    }
}