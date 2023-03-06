<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;

use BetterPayment\Installer\CustomFieldInstaller;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Framework\Event\DataMappingEvent;
use Shopware\Storefront\Page\Account\Profile\AccountProfilePage;
use Shopware\Storefront\Page\Account\Profile\AccountProfilePageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MappingRegisterCustomer implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::MAPPING_REGISTER_CUSTOMER => 'addCustomField'
        ];
    }

    public function addCustomField(DataMappingEvent $event): bool
    {
        $input = $event->getInput();
        $output = $event->getOutput();

        // get gender from form
        $gender = $input->get(CustomFieldInstaller::CUSTOMER_GENDER);

        // set gender as custom field
        $output['customFields'] = [CustomFieldInstaller::CUSTOMER_GENDER => $gender];
        $event->setOutput($output);

        return true;
    }
}