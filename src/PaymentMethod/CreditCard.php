<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\CreditCardHandler;

class CreditCard extends PaymentMethod
{
    public const SHORTNAME = 'cc';

    protected string $handler = CreditCardHandler::class;
    protected string $name = 'Credit Card';
    protected string $description = 'Credit Card description';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Credit Card (DE)',
            'description' => 'Credit Card description (DE)',
        ],
        'en-GB' => [
            'name' => 'Credit Card',
            'description' => 'Credit Card description',
        ],
    ];
}