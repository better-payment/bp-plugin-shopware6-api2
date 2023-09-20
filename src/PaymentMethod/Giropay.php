<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\GiropayHandler;

class Giropay extends PaymentMethod
{
    public const UUID = '9aa2dc141a97415c802b0a3775e55c6c';
    public const SHORTNAME = 'giro';

    protected string $id = self::UUID;
    protected string $handler = GiropayHandler::class;
    protected string $name = 'Giropay';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Giropay',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'Giropay',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}