<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\AsynchronousBetterPaymentHandler;

class Paypal extends PaymentMethod
{
    public const UUID = '713fed1249a342ea93e8a50aa3ca38ed';
    public const SHORTNAME = 'paypal';

    protected string $id = self::UUID;
    protected string $handler = AsynchronousBetterPaymentHandler::class;
    protected string $name = 'PayPal';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'PayPal',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'PayPal',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}