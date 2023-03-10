<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\PaypalHandler;

class Paypal extends PaymentMethod
{
    public const UUID = '713fed1249a342ea93e8a50aa3ca38ed';

    protected string $id = self::UUID;
    protected string $handler = PaypalHandler::class;
    protected string $name = 'PayPal';
    protected string $shortname = 'paypal';
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'PayPal',
            'description' => '',
        ],
        'en-GB' => [
            'name' => 'PayPal',
            'description' => '',
        ],
    ];
}