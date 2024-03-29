<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;

use BetterPayment\Installer\CustomFieldInstaller;
use BetterPayment\Util\ConfigReader;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\CustomField\CustomFieldEntity;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PluginConfigChangedEventSubscriber implements EventSubscriberInterface
{
    private ConfigReader $configReader;
    private SystemConfigService $systemConfigService;
    private EntityRepository $customFieldRepository;

    public function __construct(ConfigReader $configReader, SystemConfigService $systemConfigService, EntityRepository $customFieldRepository)
    {
        $this->configReader = $configReader;
        $this->systemConfigService = $systemConfigService;
        $this->customFieldRepository = $customFieldRepository;
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
        }

        if ($this->anyCollectGenderConfigChanged($event)) {
            $this->setGenderRequired($this->genderIsCollected());
        }
    }

    private function anyCollectBirthdayConfigChanged(SystemConfigChangedEvent $event): bool
    {
        return $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_DATE_OF_BIRTH
            || $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::INVOICE_COLLECT_DATE_OF_BIRTH;
    }

    private function birthdayIsCollected(): bool
    {
        return $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_DATE_OF_BIRTH)
            || $this->configReader->getBool(ConfigReader::INVOICE_COLLECT_DATE_OF_BIRTH);
    }

    private function anyCollectGenderConfigChanged(SystemConfigChangedEvent $event): bool
    {
        return $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_GENDER
            || $event->getKey() == ConfigReader::CONFIG_DOMAIN . ConfigReader::INVOICE_COLLECT_GENDER;
    }

    private function genderIsCollected(): bool
    {
        return $this->configReader->getBool(ConfigReader::SEPA_DIRECT_DEBIT_COLLECT_GENDER)
            || $this->configReader->getBool(ConfigReader::INVOICE_COLLECT_GENDER);
    }

    private function setGenderRequired(bool $genderIsCollected): void
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria([CustomFieldInstaller::CUSTOMER_GENDER_ID]);

        /** @var CustomFieldEntity $customField */
        $customField = $this->customFieldRepository->search($criteria, $context)->first();

        // Admin can delete that custom field, so check whether it exists first
        if ($customField) {
            $config = $customField->getConfig();

            // depending on flag change setting of gender custom field
            if ($genderIsCollected) {
                $config['validation'] = 'required';

                $data = [
                    [
                        'id' => $customField->getId(),
                        'active' => true,
                        'allowCustomerWrite' => true,
                        'config' => $config
                    ]
                ];
            }
            else {
                unset($config['validation']);

                $data = [
                    [
                        'id' => $customField->getId(),
                        'active' => false,
                        'allowCustomerWrite' => false,
                        'config' => $config
                    ]
                ];
            }

            $this->customFieldRepository->update($data, $context);
        }
    }
}