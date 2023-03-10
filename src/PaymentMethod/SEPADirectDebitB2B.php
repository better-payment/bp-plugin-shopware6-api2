<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\SEPADirectDebitB2BHandler;

class SEPADirectDebitB2B extends PaymentMethod
{
    public const UUID = 'c3c78c3fdbdc4fc78e67291eab2ee617';

    protected string $id = self::UUID;
    protected string $handler = SEPADirectDebitB2BHandler::class;
    protected string $name = 'SEPA Direct Debit B2B';
    protected string $shortname = 'dd_b2b';
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'SEPA-Lastschrift B2B',
            'description' => '',
        ],
        'en-GB' => [
            'name' => 'SEPA Direct Debit B2B',
            'description' => '',
        ],
    ];
}