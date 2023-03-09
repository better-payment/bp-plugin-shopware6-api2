<?php declare(strict_types=1);

namespace BetterPayment\EventSubscriber;

use BetterPayment\BetterPayment;
use Shopware\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PluginPostActivateEventSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginPostActivateEvent::class => 'onPluginActivate',
        ];
    }

    public function onPluginActivate(PluginPostActivateEvent $event): void
    {
        if ($event->getPlugin()->getBaseClass() == BetterPayment::class)
        {
            $this->systemConfigService->set('core.loginRegistration.showPhoneNumberField', true);
            $this->systemConfigService->set('core.loginRegistration.showAccountTypeSelection', true);
        }
    }
}