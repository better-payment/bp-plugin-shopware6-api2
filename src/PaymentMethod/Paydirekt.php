<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\PaydirektHandler;

class Paydirekt extends PaymentMethod
{
    protected string $handler = PaydirektHandler::class;
    protected string $name = 'Paydirekt';
    protected string $shortname = 'paydirekt';
    protected string $description = 'Paydirekt description';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Paydirekt (DE)',
            'description' => 'Paydirekt description (DE)',
        ],
        'en-GB' => [
            'name' => 'Paydirekt',
            'description' => 'Paydirekt description',
        ],
    ];
}