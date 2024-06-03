<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\AsynchronousBetterPaymentHandler;

class Paydirekt extends PaymentMethod
{
    public const UUID = 'bfee4cb85d334f5382e2a4c72674263b';
    public const SHORTNAME = 'paydirekt';

    protected string $id = self::UUID;
    protected string $handler = AsynchronousBetterPaymentHandler::class;
    protected string $name = 'Paydirekt';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Paydirekt',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'Paydirekt',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}