<?php declare(strict_types=1);

namespace BetterPayment;

use BetterPayment\Installer\PaymentMethodInstaller;
use BetterPayment\Installer\CustomFieldInstaller;
use BetterPayment\Installer\RuleInstaller;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

class BetterPayment extends Plugin
{
	public const PLUGIN_NAME = 'BetterPayment';
    public function install(InstallContext $installContext): void
    {
        $this->getPaymentMethodInstaller()->install($installContext);
        $this->getCustomFieldInstaller()->install($installContext);
        $this->getRuleInstaller()->install($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->getPaymentMethodInstaller()->update($updateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $this->getPaymentMethodInstaller()->uninstall($uninstallContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->getPaymentMethodInstaller()->activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->getPaymentMethodInstaller()->deactivate($deactivateContext);
    }

    private function getRuleInstaller(): RuleInstaller
    {
        /** @var EntityRepository $ruleRepository */
        $ruleRepository = $this->container->get('rule.repository');

        return new RuleInstaller($ruleRepository);
    }

    public function getPaymentMethodInstaller(): PaymentMethodInstaller
    {
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        /** @var EntityRepository $paymentMethodRepository */
        $paymentMethodRepository = $this->container->get('payment_method.repository');

        return new PaymentMethodInstaller($pluginIdProvider, $paymentMethodRepository);
    }

    private function getCustomFieldInstaller(): CustomFieldInstaller
    {
        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        return new CustomFieldInstaller($customFieldSetRepository);
    }


}