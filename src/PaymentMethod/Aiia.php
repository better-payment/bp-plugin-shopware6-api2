<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\AsynchronousBetterPaymentHandler;

class Aiia extends PaymentMethod
{
    public const UUID = 'e9b2d1a6c89b4e8d8a5b16a5c0b5b5e9';
    public const SHORTNAME = 'aiia';

    protected string $id = self::UUID;
    protected string $handler = AsynchronousBetterPaymentHandler::class;
    protected string $name = 'Aiia Pay';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Aiia Pay',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'Aiia Pay',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}