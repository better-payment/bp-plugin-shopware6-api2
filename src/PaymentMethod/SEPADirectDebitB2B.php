<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\SynchronousBetterPaymentHandler;

class SEPADirectDebitB2B extends PaymentMethod
{
    public const UUID = 'c3c78c3fdbdc4fc78e67291eab2ee617';
    public const SHORTNAME = 'dd_b2b';

    protected string $id = self::UUID;
    protected string $handler = SynchronousBetterPaymentHandler::class;
    protected string $name = 'SEPA Direct Debit B2B';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'SEPA-Lastschrift B2B',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'SEPA Direct Debit B2B',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}