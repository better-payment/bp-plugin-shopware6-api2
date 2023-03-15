<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\SofortHandler;

class Sofort extends PaymentMethod
{
    public const UUID = 'd3fe50176f7b49cbbdaf8c3182f27890';
    public const SHORTNAME = 'sofort';

    protected string $id = self::UUID;
    protected string $handler = SofortHandler::class;
    protected string $name = 'Sofort';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Sofortuberweisung',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'Sofort',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}