<?php declare(strict_types=1);

namespace BetterPayment\Util;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigReader
{
    private SystemConfigService $systemConfigService;
    private const CONFIG_DOMAIN = 'BetterPayment.config.';

    public const ENVIRONMENT = 'environment';
    public const WHITE_LABEL = 'whiteLabel';
    public const TEST_API_KEY = 'testAPIKey';
    public const TEST_OUTGOING_KEY = 'testOutgoingKey';
    public const TEST_INCOMING_KEY = 'testIncomingKey';
    public const PRODUCTION_API_KEY = 'productionAPIKey';
    public const PRODUCTION_OUTGOING_KEY = 'productionOutgoingKey';
    public const PRODUCTION_INCOMING_KEY = 'productionIncomingKey';


    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function get(string $key): string
    {
        return $this->systemConfigService->get(self::CONFIG_DOMAIN . $key);
    }

    public function getAPIHostName(): string
    {
        $whiteLabel = $this->get(self::WHITE_LABEL);
        $environment = $this->get(self::ENVIRONMENT);

        $data = json_decode(file_get_contents(__DIR__.'/../Resources/data/whitelabels.json'), true);

        return $data[$whiteLabel][$environment]['api_hostname'];
    }
}