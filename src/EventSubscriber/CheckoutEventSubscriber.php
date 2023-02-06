<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;

use BetterPayment\PaymentHandler\SEPADirectDebitB2BHandler;
use BetterPayment\PaymentHandler\SEPADirectDebitHandler;
use BetterPayment\Storefront\Struct\CheckoutData;
use BetterPayment\Util\ConfigReader;
use Shopware\Core\Framework\Uuid\Uuid;
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
//            AccountEditOrderPageLoadedEvent::class => 'addForm'
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
                'creditorID' => $this->configReader->get('sepaDirectDebitCreditorID'),
                'companyName' => $this->configReader->get('sepaDirectDebitCompanyName'),
                'mandateReference' => Uuid::randomHex(),
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
        elseif ($paymentMethod->getHandlerIdentifier() == SEPADirectDebitB2BHandler::class) {
            $data = new CheckoutData();

            $data->assign([
                'template' => '@Storefront/betterpayment/sepa-direct-debit.html.twig',
                'creditorID' => $this->configReader->get('sepaDirectDebitB2BCreditorID'),
                'companyName' => $this->configReader->get('sepaDirectDebitB2BCompanyName'),
                'mandateReference' => Uuid::randomHex(),
            ]);

            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
    }
}