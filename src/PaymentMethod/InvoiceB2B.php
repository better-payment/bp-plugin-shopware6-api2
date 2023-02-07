<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\InvoiceB2BHandler;

class InvoiceB2B extends PaymentMethod
{
    public const SHORTNAME = 'kar_b2b';

    protected string $handler = InvoiceB2BHandler::class;
    protected string $name = 'Invoice (B2B)';
    protected string $description = 'Invoice (B2B) description';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Invoice (B2B) (DE)',
            'description' => 'Invoice (B2B) description (DE)',
        ],
        'en-GB' => [
            'name' => 'Invoice (B2B)',
            'description' => 'Invoice (B2B) description',
        ],
    ];
}