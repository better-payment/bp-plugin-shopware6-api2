<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\CreditCardHandler;

class CreditCard extends PaymentMethod
{
    protected string $handler = CreditCardHandler::class;
    protected string $name = 'Credit Card - cc';
    protected string $description = 'Credit Card description';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Credit Card - cc (DE)',
            'description' => 'Credit Card description (DE)',
        ],
        'en-GB' => [
            'name' => 'Credit Card - cc',
            'description' => 'Credit Card description',
        ],
    ];
}