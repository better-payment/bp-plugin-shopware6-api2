<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\SEPADirectDebitHandler;

class SEPADirectDebit extends PaymentMethod
{
    public const SHORTNAME = 'dd';

    protected string $handler = SEPADirectDebitHandler::class;
    protected string $name = 'SEPA Direct Debit';
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'SEPA-Lastschrift',
            'description' => '',
        ],
        'en-GB' => [
            'name' => 'SEPA Direct Debit',
            'description' => '',
        ],
    ];
}