<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\InvoiceHandler;

class Invoice extends PaymentMethod
{
    protected string $handler = InvoiceHandler::class;
    protected string $name = 'Invoice';
    protected string $shortname = 'kar';
    protected string $description = 'Invoice description';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Invoice (DE)',
            'description' => 'Invoice description (DE)',
        ],
        'en-GB' => [
            'name' => 'Invoice',
            'description' => 'Invoice description',
        ],
    ];
}