<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\GiropayHandler;

class Giropay extends PaymentMethod
{
    public const UUID = '2189673d76af71afa498914ccdb0c9b2';
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