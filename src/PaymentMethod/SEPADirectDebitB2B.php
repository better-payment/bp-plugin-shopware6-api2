<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\SEPADirectDebitB2BHandler;

class SEPADirectDebitB2B extends PaymentMethod
{
    public const SHORTNAME = 'dd_b2b';

    protected string $handler = SEPADirectDebitB2BHandler::class;
    protected string $name = 'SEPA Direct Debit (B2B)';
    protected string $description = 'SEPA Direct Debit (B2B) description';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'SEPA Direct Debit (B2B) (DE)',
            'description' => 'SEPA Direct Debit (B2B) description (DE)',
        ],
        'en-GB' => [
            'name' => 'SEPA Direct Debit (B2B)',
            'description' => 'SEPA Direct Debit (B2B) description',
        ],
    ];
}