<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\PaydirektHandler;

class Paydirekt extends PaymentMethod
{
    public const UUID = 'bfee4cb85d334f5382e2a4c72674263b';
    public const SHORTNAME = 'paydirekt';

    protected string $handler = PaydirektHandler::class;
    protected string $name = 'Paydirekt';
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Paydirekt',
            'description' => '',
        ],
        'en-GB' => [
            'name' => 'Paydirekt',
            'description' => '',
        ],
    ];
}