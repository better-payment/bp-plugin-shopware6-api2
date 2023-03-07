<?php declare(strict_types=1);

namespace BetterPayment;

use BetterPayment\Installer\PaymentMethodInstaller;
use BetterPayment\Installer\CustomFieldInstaller;
use BetterPayment\Installer\RuleInstaller;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

class BetterPayment extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        $this->getPaymentMethodInstaller()->install($installContext);
        $this->getCustomFieldInstaller()->install($installContext);
        $this->getRuleInstaller()->install($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext); // TODO: Change the autogenerated stub
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

    public function getPaymentMethodInstaller(): PaymentMethodInstaller
    {
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        /** @var EntityRepositoryInterface $paymentMethodRepository */
        $paymentMethodRepository = $this->container->get('payment_method.repository');

        return new PaymentMethodInstaller($pluginIdProvider, $paymentMethodRepository);
    }

    private function getCustomFieldInstaller(): CustomFieldInstaller
    {
        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        return new CustomFieldInstaller($customFieldSetRepository);
    }

    private function getRuleInstaller(): RuleInstaller
    {
        /** @var EntityRepositoryInterface $ruleRepository */
        $ruleRepository = $this->container->get('rule.repository');

        return new RuleInstaller($ruleRepository);
    }
}