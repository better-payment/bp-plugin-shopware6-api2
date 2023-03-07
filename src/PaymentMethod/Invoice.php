<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\InvoiceHandler;

class Invoice extends PaymentMethod
{
    public const UUID = '52226680b1574d13a64c8139af95bcfc';
    public const SHORTNAME = 'kar';

    protected string $handler = InvoiceHandler::class;
    protected string $name = 'Invoice';
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