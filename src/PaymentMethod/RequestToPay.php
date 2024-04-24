<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

use BetterPayment\PaymentHandler\RequestToPayHandler;

class RequestToPay extends PaymentMethod
{
    public const UUID = '6e2b8c4a0f1b49d5ae7af1e2a58174d6';
    public const SHORTNAME = 'rtp';

    protected string $id = self::UUID;
    protected string $handler = RequestToPayHandler::class;
    protected string $name = 'Request To Pay';
    protected string $shortname = self::SHORTNAME;
    protected string $description = '';
    protected string $icon = '';
    protected array $translations = [
        'de-DE' => [
            'name' => 'Request To Pay',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
        'en-GB' => [
            'name' => 'Request To Pay',
            'description' => '',
            'customFields' => [
                'shortname' => self::SHORTNAME
            ]
        ],
    ];
}