<?php

namespace BetterPayment\EventSubscriber;

use BetterPayment\PaymentHandler\SEPADirectDebitHandler;
use BetterPayment\Storefront\Struct\CheckoutData;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutEventSubscriber implements EventSubscriberInterface
{
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
            $page->addExtension(CheckoutData::EXTENSION_NAME, $data);
        }
    }
}