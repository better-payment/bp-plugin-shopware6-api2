<?php declare(strict_types=1);

namespace BetterPayment\Util;

use BetterPayment\BetterPayment;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\PluginService;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigReader
{
    public const CONFIG_DOMAIN = 'BetterPayment.config.';

    public const ENVIRONMENT = 'environment';
    public const TEST_API_URL = 'testAPIUrl';
    public const TEST_API_KEY = 'testAPIKey';
    public const TEST_OUTGOING_KEY = 'testOutgoingKey';
    public const TEST_INCOMING_KEY = 'testIncomingKey';
    public const PRODUCTION_API_URL = 'productionAPIUrl';
    public const PRODUCTION_API_KEY = 'productionAPIKey';
    public const PRODUCTION_OUTGOING_KEY = 'productionOutgoingKey';
    public const PRODUCTION_INCOMING_KEY = 'productionIncomingKey';

    public const SEPA_DIRECT_DEBIT_CREDITOR_ID = 'sepaDirectDebitCreditorID';
    public const SEPA_DIRECT_DEBIT_COMPANY_NAME = 'sepaDirectDebitCompanyName';
    public const SEPA_DIRECT_DEBIT_COLLECT_DATE_OF_BIRTH = 'sepaDirectDebitCollectDateOfBirth';
    public const SEPA_DIRECT_DEBIT_COLLECT_GENDER = 'sepaDirectDebitCollectGender';
    public const SEPA_DIRECT_DEBIT_RISK_CHECK_AGREEMENT = 'sepaDirectDebitRiskCheckAgreement';
    public const SEPA_DIRECT_DEBIT_ENABLE_MANUAL_CAPTURE = 'sepaDirectDebitEnableManualCapture';
    public const SEPA_DIRECT_DEBIT_AUTOMATICALLY_CAPTURE_ON_ORDER_INVOICE_DOCUMENT_SENT = 'sepaDirectDebitAutomaticallyCaptureOnOrderInvoiceDocumentSent';

    public const SEPA_DIRECT_DEBIT_B2B_CREDITOR_ID = 'sepaDirectDebitB2BCreditorID';
    public const SEPA_DIRECT_DEBIT_B2B_COMPANY_NAME = 'sepaDirectDebitB2BCompanyName';
    public const SEPA_DIRECT_DEBIT_B2B_RISK_CHECK_AGREEMENT = 'sepaDirectDebitB2BRiskCheckAgreement';
    public const SEPA_DIRECT_DEBIT_B2B_ENABLE_MANUAL_CAPTURE = 'sepaDirectDebitB2BEnableManualCapture';
    public const SEPA_DIRECT_DEBIT_B2B_AUTOMATICALLY_CAPTURE_ON_ORDER_INVOICE_DOCUMENT_SENT = 'sepaDirectDebitB2BAutomaticallyCaptureOnOrderInvoiceDocumentSent';

    public const INVOICE_DISPLAY_INSTRUCTION = 'invoiceDisplayInstruction';
    public const INVOICE_IBAN = 'invoiceIBAN';
    public const INVOICE_BIC = 'invoiceBIC';
    public const INVOICE_COLLECT_DATE_OF_BIRTH = 'invoiceCollectDateOfBirth';
    public const INVOICE_COLLECT_GENDER = 'invoiceCollectGender';
    public const INVOICE_RISK_CHECK_AGREEMENT = 'invoiceRiskCheckAgreement';
    public const INVOICE_AUTOMATICALLY_CAPTURE_ON_ORDER_INVOICE_DOCUMENT_SENT = 'invoiceAutomaticallyCaptureOnOrderInvoiceDocumentSent';

    public const INVOICE_B2B_DISPLAY_INSTRUCTION = 'invoiceB2BDisplayInstruction';
    public const INVOICE_B2B_IBAN = 'invoiceB2BIBAN';
    public const INVOICE_B2B_BIC = 'invoiceB2BBIC';
    public const INVOICE_B2B_RISK_CHECK_AGREEMENT = 'invoiceB2BRiskCheckAgreement';
    public const INVOICE_B2B_AUTOMATICALLY_CAPTURE_ON_ORDER_INVOICE_DOCUMENT_SENT = 'invoiceB2BAutomaticallyCaptureOnOrderInvoiceDocumentSent';

    public const APPLE_PAY_3DS_ENABLED = 'applePay3dsEnabled';
    public const APPLE_PAY_SUPPORTED_NETWORKS = 'applePaySupportedNetworks';

    public const GOOGLE_PAY_ALLOWED_CARD_NETWORKS = 'googlePayAllowedCardNetworks';
    public const GOOGLE_PAY_ALLOWED_AUTH_METHODS = 'googlePayAllowedAuthMethods';
    public const GOOGLE_PAY_GATEWAY_ID = 'googlePayGateway';
    public const GOOGLE_PAY_GATEWAY_MERCHANT_ID = 'googlePayGatewayMerchantId';
    public const GOOGLE_PAY_MERCHANT_ID = 'googlePayMerchantId';
    public const GOOGLE_PAY_MERCHANT_NAME = 'googlePayMerchantName';


    private SystemConfigService $systemConfigService;
    private PluginService $pluginService;
    private string $shopwareVersion;

    public function __construct(
        SystemConfigService $systemConfigService,
        PluginService $pluginService,
        string $shopwareVersion
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->pluginService = $pluginService;
        $this->shopwareVersion = $shopwareVersion;
    }

    public function getSystemConfig(string $key, string $salesChannelId)
    {
        return $this->systemConfigService->get($key, $salesChannelId);
    }

    public function get(string $key)
    {
        return $this->systemConfigService->get(self::CONFIG_DOMAIN . $key);
    }

    public function getString(string $key): string
    {
        return $this->systemConfigService->getString(self::CONFIG_DOMAIN . $key);
    }

    public function getBool(string $key): bool
    {
        return $this->systemConfigService->getBool(self::CONFIG_DOMAIN . $key);
    }

    public function getAPIUrl(): string
    {
        $apiUrl = $this->get(self::ENVIRONMENT) == 'test'
            ? $this->getString(self::TEST_API_URL)
            : $this->getString(self::PRODUCTION_API_URL);

        return rtrim($apiUrl, '/');
    }

    public function getAPIKey(): string
    {
        return $this->get(self::ENVIRONMENT) == 'test'
            ? $this->getString(self::TEST_API_KEY)
            : $this->getString(self::PRODUCTION_API_KEY);
    }

    public function getOutgoingKey(): string
    {
        return $this->get(self::ENVIRONMENT) == 'test'
            ? $this->getString(self::TEST_OUTGOING_KEY)
            : $this->getString(self::PRODUCTION_OUTGOING_KEY);
    }

    public function getIncomingKey(): string
    {
        return $this->get(self::ENVIRONMENT) == 'test'
            ? $this->getString(self::TEST_INCOMING_KEY)
            : $this->getString(self::PRODUCTION_INCOMING_KEY);
    }

    public function getAppUrl(): string
    {
        return rtrim(EnvironmentHelper::getVariable('APP_URL'), '/');
    }

    public function getPostbackUrl(): string
    {
        return $this->getAppUrl() . '/api/betterpayment/webhook';
    }

    public function getAppName(): string
    {
        return 'Shopware 6';
    }

    public function getAppVersion(): string
    {
        return 'SW ' . $this->shopwareVersion . ', Plugin ' . $this->pluginService->getPluginByName(BetterPayment::PLUGIN_NAME, Context::createDefaultContext())->getVersion();
    }
}