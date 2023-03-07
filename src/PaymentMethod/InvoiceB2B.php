<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\InvoiceB2BHandler;

class InvoiceB2B extends PaymentMethod
{
    public const UUID = '6f6d23fad4fd46529b042338f81f2dd8';
    public const SHORTNAME = 'kar_b2b';

    protected string $id = self::UUID;
    protected string $handler = InvoiceB2BHandler::class;
    protected string $name = 'Invoice B2B';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Kauf auf Rechnung',
            'description' => '',
        ],
        'en-GB' => [
            'name' => 'Invoice',
            'description' => '',
        ],
    ];
}