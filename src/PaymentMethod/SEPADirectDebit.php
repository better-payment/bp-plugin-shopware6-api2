<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\SynchronousBetterPaymentHandler;

class SEPADirectDebit extends PaymentMethod
{
    public const UUID = '72b81c938115438cb76a409c60a9d20a';
    public const SHORTNAME = 'dd';

    protected string $id = self::UUID;
    protected string $handler = SynchronousBetterPaymentHandler::class;
    protected string $name = 'SEPA Direct Debit';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'SEPA-Lastschrift',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'SEPA Direct Debit',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}