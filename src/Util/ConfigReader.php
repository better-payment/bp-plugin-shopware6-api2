<?php declare(strict_types=1);

namespace BetterPayment\Util;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigReader
{
    private SystemConfigService $systemConfigService;

    public const CONFIG_DOMAIN = 'BetterPayment.config.';

    public const ENVIRONMENT = 'environment';
    public const WHITE_LABEL = 'whiteLabel';
    public const TEST_API_KEY = 'testAPIKey';
    public const TEST_OUTGOING_KEY = 'testOutgoingKey';
    public const TEST_INCOMING_KEY = 'testIncomingKey';
    public const PRODUCTION_API_KEY = 'productionAPIKey';
    public const PRODUCTION_OUTGOING_KEY = 'productionOutgoingKey';
    public const PRODUCTION_INCOMING_KEY = 'productionIncomingKey';

    public const SEPA_DIRECT_DEBIT_CREDITOR_ID = 'sepaDirectDebitCreditorID';
    public const SEPA_DIRECT_DEBIT_COMPANY_NAME = 'sepaDirectDebitCompanyName';
    public const SEPA_DIRECT_DEBIT_COLLECT_DATE_OF_BIRTH = 'sepaDirectDebitCollectDateOfBirth';
    public const SEPA_DIRECT_DEBIT_COLLECT_GENDER = 'sepaDirectDebitCollectGender';
    public const SEPA_DIRECT_DEBIT_RISK_CHECK_AGREEMENT = 'sepaDirectDebitRiskCheckAgreement';

    public const SEPA_DIRECT_DEBIT_B2B_CREDITOR_ID = 'sepaDirectDebitB2BCreditorID';
    public const SEPA_DIRECT_DEBIT_B2B_COMPANY_NAME = 'sepaDirectDebitB2BCompanyName';
    public const SEPA_DIRECT_DEBIT_B2B_COLLECT_DATE_OF_BIRTH = 'sepaDirectDebitB2BCollectDateOfBirth';
    public const SEPA_DIRECT_DEBIT_B2B_COLLECT_GENDER = 'sepaDirectDebitB2BCollectGender';
    public const SEPA_DIRECT_DEBIT_B2B_RISK_CHECK_AGREEMENT = 'sepaDirectDebitB2BRiskCheckAgreement';

    public const INVOICE_DISPLAY_INSTRUCTION = 'invoiceDisplayInstruction';
    public const INVOICE_IBAN = 'invoiceIBAN';
    public const INVOICE_BIC = 'invoiceBIC';
    public const INVOICE_COLLECT_DATE_OF_BIRTH = 'invoiceCollectDateOfBirth';
    public const INVOICE_COLLECT_GENDER = 'invoiceCollectGender';
    public const INVOICE_RISK_CHECK_AGREEMENT = 'invoiceRiskCheckAgreement';

    public const INVOICE_B2B_DISPLAY_INSTRUCTION = 'invoiceB2BDisplayInstruction';
    public const INVOICE_B2B_IBAN = 'invoiceB2BIBAN';
    public const INVOICE_B2B_BIC = 'invoiceB2BBIC';
    public const INVOICE_B2B_COLLECT_DATE_OF_BIRTH = 'invoiceB2BCollectDateOfBirth';
    public const INVOICE_B2B_COLLECT_GENDER = 'invoiceB2BCollectGender';
    public const INVOICE_B2B_RISK_CHECK_AGREEMENT = 'invoiceB2BRiskCheckAgreement';
    

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
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

    public function getAPIHostName(): string
    {
        $whiteLabel = $this->get(self::WHITE_LABEL);
        $environment = $this->get(self::ENVIRONMENT);

        $data = json_decode(file_get_contents(__DIR__.'/../Resources/data/whitelabels.json'), true);

        return $data[$whiteLabel][$environment]['api_hostname'];
    }

    public function getAPIKey(): string
    {
        return $this->get(self::ENVIRONMENT) == 'test' ? $this->getString(self::TEST_API_KEY) : $this->getString(self::PRODUCTION_API_KEY);
    }

    public function getOutgoingKey(): string
    {
        return $this->get(self::ENVIRONMENT) == 'test' ? $this->getString(self::TEST_OUTGOING_KEY) : $this->getString(self::PRODUCTION_OUTGOING_KEY);
    }

    public function getIncomingKey(): string
    {
        return $this->get(self::ENVIRONMENT) == 'test' ? $this->getString(self::TEST_INCOMING_KEY) : $this->getString(self::PRODUCTION_INCOMING_KEY);
    }
}