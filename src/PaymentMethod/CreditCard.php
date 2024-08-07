<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\AsynchronousBetterPaymentHandler;

class CreditCard extends PaymentMethod
{
    public const UUID = '818126dcd4e14c3ca658e935d032f73b';
    public const SHORTNAME = 'cc';

    protected string $id = self::UUID;
    protected string $handler = AsynchronousBetterPaymentHandler::class;
    protected string $name = 'Credit Card';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Kreditkarte',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'Credit Card',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}