<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\SynchronousBetterPaymentHandler;

class Invoice extends PaymentMethod
{
    public const UUID = '52226680b1574d13a64c8139af95bcfc';
    public const SHORTNAME = 'kar';

    protected string $id = self::UUID;
    protected string $handler = SynchronousBetterPaymentHandler::class;
    protected string $name = 'Invoice';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Kauf auf Rechnung',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'Invoice',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}