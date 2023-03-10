<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\SEPADirectDebitHandler;

class SEPADirectDebit extends PaymentMethod
{
    public const UUID = '72b81c938115438cb76a409c60a9d20a';

    protected string $id = self::UUID;
    protected string $handler = SEPADirectDebitHandler::class;
    protected string $name = 'SEPA Direct Debit';
    protected string $shortname = 'dd';
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