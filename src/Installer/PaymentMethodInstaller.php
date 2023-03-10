<?php declare(strict_types=1);

namespace BetterPayment\Installer;

use BetterPayment\BetterPayment;
use BetterPayment\PaymentMethod\InvoiceB2B;
use BetterPayment\PaymentMethod\PaymentMethod;
use BetterPayment\PaymentMethod\CreditCard;
use BetterPayment\PaymentMethod\Invoice;
use BetterPayment\PaymentMethod\Paydirekt;
use BetterPayment\PaymentMethod\Paypal;
use BetterPayment\PaymentMethod\SEPADirectDebit;
use BetterPayment\PaymentMethod\SEPADirectDebitB2B;
use BetterPayment\PaymentMethod\Sofort;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

class PaymentMethodInstaller
{
    public const PAYMENT_METHODS = [
        CreditCard::class,
        Paydirekt::class,
        Sofort::class,
        Paypal::class,
        SEPADirectDebit::class,
        SEPADirectDebitB2B::class,
        Invoice::class,
        InvoiceB2B::class
    ];

    private PluginIdProvider $pluginIdProvider;
    private EntityRepositoryInterface $paymentMethodRepository;

    /**
     * @param PluginIdProvider $pluginIdProvider
     * @param EntityRepositoryInterface $paymentMethodRepository
     */
    public function __construct(PluginIdProvider $pluginIdProvider, EntityRepositoryInterface $paymentMethodRepository)
    {
        $this->pluginIdProvider = $pluginIdProvider;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function install(InstallContext $installContext): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->upsertPaymentMethod($paymentMethod, $installContext->getContext());
        }
    }

    public function update(UpdateContext $updateContext): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->upsertPaymentMethod($paymentMethod, $updateContext->getContext());
        }
    }

    // Only set the payment method to inactive when uninstalling. Removing the payment method would
    // cause data consistency issues, since the payment method might have been used in several orders
    public function uninstall(UninstallContext $uninstallContext): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->setPaymentMethodIsActive($paymentMethod, false, $uninstallContext->getContext());
        }
    }

    // Activate all payment methods
    public function activate(ActivateContext $activateContext): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->setPaymentMethodIsActive($paymentMethod, true, $activateContext->getContext());
        }
    }

    // De-activate all payment methods
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->setPaymentMethodIsActive($paymentMethod, false, $deactivateContext->getContext());
        }
    }

    // Instantiate an instance of each payment method and add it to the list
    private function getPaymentMethods(): array
    {
        $paymentMethods = [];

        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $paymentMethods[] = new $paymentMethod();
        }

        return $paymentMethods;
    }

    private function upsertPaymentMethod(PaymentMethod $paymentMethod, Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(BetterPayment::class, $context);

        $paymentMethodData = [
            'id' => $paymentMethod->getId(),
            'pluginId' => $pluginId,
            'handlerIdentifier' => $paymentMethod->getHandler(),
            'name' => $paymentMethod->getName(),
            'description' => $paymentMethod->getDescription(),
            'translations' => $paymentMethod->getTranslations(),
            'afterOrderEnabled' => true
        ];

        $this->paymentMethodRepository->upsert([$paymentMethodData], $context);
    }

    private function setPaymentMethodIsActive(PaymentMethod $paymentMethod, bool $active, Context $context): void
    {
        $paymentMethodData = [
            'id' => $paymentMethod->getId(),
            'active' => $active,
        ];

        $this->paymentMethodRepository->update([$paymentMethodData], $context);
    }
}