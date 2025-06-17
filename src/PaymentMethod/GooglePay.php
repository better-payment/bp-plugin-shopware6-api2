<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\GooglePayPaymentHandler;

class GooglePay extends PaymentMethod
{
    public const UUID = '0197415c479d73cca0ef9b686275ffce';
    public const SHORTNAME = 'google_pay';

    protected string $id = self::UUID;
    protected string $handler = GooglePayPaymentHandler::class;
    protected string $name = 'Google Pay';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Google Pay',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'Google Pay',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}