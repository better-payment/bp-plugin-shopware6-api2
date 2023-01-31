<?php

namespace BetterPayment\Storefront\Struct;

use Shopware\Core\Framework\Struct\Struct;

class CheckoutData extends Struct
{
    public const EXTENSION_NAME = 'betterpayment';

    // TODO implement using getter and setters ?
    public string $template = '@Storefront/betterpayment/sepa-direct-debit.html.twig';
}