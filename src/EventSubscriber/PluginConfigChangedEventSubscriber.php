<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;

use BetterPayment\Util\ConfigReader;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PluginConfigChangedEventSubscriber implements EventSubscriberInterface
{
    private ConfigReader $configReader;
    private SystemConfigService $systemConfigService;

    public function __construct(ConfigReader $configReader, SystemConfigService $systemConfigService)
    {
        $this->configReader = $configReader;
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onPluginConfigChanged',
        ];
    }

    public function onPluginConfigChanged(SystemConfigChangedEvent $event): void
    {
        if ($this->anyCollectBirthdayConfigChanged($event))
        {
            $this->systemConfigService->set('core.loginRegistration.showBirthdayField', $this->birthdayIsCollected());
            $this->systemConfigService->set('core.loginRegistration.birthdayFieldRequired', $this->birthdayIsCollected());
        }

        // TODO: set gender customfield active and required which was installed during plugin install or manually
        // Admin can delete that custom field, so check whether it exists first
//        if ($this->anyCollectGenderConfigChanged($event)) {
//
//        }
    }

    private function anyCollectBirthdayConfigChanged(SystemConfigChangedEvent $event): bool
    {
        return $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_DATE_OF_BIRTH
            || $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::SEPA_DIRECT_DEBIT_B2B_COLLECT_DATE_OF_BIRTH
            || $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::INVOICE_COLLECT_DATE_OF_BIRTH
            || $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::INVOICE_B2B_COLLECT_DATE_OF_BIRTH;
    }

    private function birthdayIsCollected(): bool
    {
        return $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_DATE_OF_BIRTH)
            || $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_B2B_COLLECT_DATE_OF_BIRTH)
            || $this->configReader->getBool(ConfigReader::INVOICE_COLLECT_DATE_OF_BIRTH)
            || $this->configReader->getBool(ConfigReader::INVOICE_B2B_COLLECT_DATE_OF_BIRTH);
    }

    private function anyCollectGenderConfigChanged(SystemConfigChangedEvent $event): bool
    {
        return $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_GENDER
            || $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::SEPA_DIRECT_DEBIT_B2B_COLLECT_GENDER
            || $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::INVOICE_COLLECT_GENDER
            || $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::INVOICE_B2B_COLLECT_GENDER;
    }

    private function genderIsCollected(): bool
    {
        return $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_GENDER)
            || $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_B2B_COLLECT_GENDER)
            || $this->configReader->getBool(ConfigReader::INVOICE_COLLECT_GENDER)
            || $this->configReader->getBool(ConfigReader::INVOICE_B2B_COLLECT_GENDER);
    }
}