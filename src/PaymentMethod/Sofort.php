<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\SofortHandler;

class Sofort extends PaymentMethod
{
    public const SHORTNAME = 'sofort';

    protected string $handler = SofortHandler::class;
    protected string $name = 'Sofort';
    protected string $description = 'Sofort description';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Sofort (DE)',
            'description' => 'Sofort description (DE)',
        ],
        'en-GB' => [
            'name' => 'Sofort',
            'description' => 'Sofort description',
        ],
    ];
}