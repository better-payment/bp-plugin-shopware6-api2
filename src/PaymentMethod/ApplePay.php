<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\ApplePayPaymentHandler;

class ApplePay extends PaymentMethod
{
    public const UUID = '06b4c03e28f74bc190f1db7513c68366';
    public const SHORTNAME = 'apple_pay';

    protected string $id = self::UUID;
    protected string $handler = ApplePayPaymentHandler::class;
    protected string $name = 'Apple Pay';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Apple Pay',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'Apple Pay',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}