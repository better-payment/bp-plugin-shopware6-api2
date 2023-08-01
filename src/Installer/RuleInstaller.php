<?php declare(strict_types=1);

namespace BetterPayment\Installer;

use BetterPayment\PaymentMethod\Invoice;
use BetterPayment\PaymentMethod\InvoiceB2B;
use BetterPayment\PaymentMethod\SEPADirectDebit;
use BetterPayment\PaymentMethod\SEPADirectDebitB2B;
use Shopware\Core\Checkout\Customer\Rule\IsCompanyRule;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Rule\Container\AndRule;

class RuleInstaller
{
    // NOTE: IDs are hard coded to easily use upsert()

    private const CUSTOMER_IS_PRIVATE_RULE_ID = 'ea5c01b126ac4cd7861bc6daeff9bc3d';
    private const CUSTOMER_IS_COMPANY_RULE_ID = 'fbac3618c6a2477ba079b31c07bb52fa';

    private EntityRepository $ruleRepository;

    public function __construct(EntityRepository $ruleRepository)
    {
        $this->ruleRepository = $ruleRepository;
    }

    public function install(InstallContext $installContext): void
    {
        $this->ruleRepository->upsert($this->getRules(), $installContext->getContext());
    }

    // Other lifecycle methods can be implemented when required, now no need for them

    private function getRules(): array
    {
        return [
            [
                'id' => self::CUSTOMER_IS_PRIVATE_RULE_ID,
                'name' => 'Private Customer',
                'description' => 'This rule is to decide whether customer is private',
                'priority' => 1,
                // conditions are formulated as OR(AND(IsCompany, *), *) means any other conditions can be added instead of * later on from administration
                'conditions' => [
                    [
                        'id' => '265d0445926a42d5a56d08d8689f6311',
                        'type' => (new AndRule())->getName(),
                        'children' => [
                            [
                                'id' => '0789def0e89d4688ab27a3d7a7a55b2f',
                                'type' => (new IsCompanyRule())->getName(),
                                'value' => [
                                    'isCompany' => false
                                ]
                            ]
                        ]
                    ]
                ],
                'paymentMethods' => [
                    [
                        'id' => SEPADirectDebit::UUID
                    ],
                    [
                        'id' => Invoice::UUID
                    ]
                ],
            ],
            [
                'id' => self::CUSTOMER_IS_COMPANY_RULE_ID,
                'name' => 'Commercial Customer',
                'description' => 'This rule is to decide whether customer is company',
                'priority' => 1,
                // conditions are formulated as OR(AND(IsCompany, *), *) means any 
                // other conditions can be added instead of * later on from administration
                'conditions' => [
                    [
                        'id' => 'd16ab13da17745feb736d5de805c4424',
                        'type' => (new AndRule())->getName(),
                        'children' => [
                            [
                                'id' => '7740c6559d7f44b687508be4514efb6b',
                                'type' => (new IsCompanyRule())->getName(),
                                'value' => [
                                    'isCompany' => true
                                ]
                            ]
                        ]
                    ]
                ],
                'paymentMethods' => [
                    [
                        'id' => SEPADirectDebitB2B::UUID
                    ],
                    [
                        'id' => InvoiceB2B::UUID
                    ]
                ],
            ],
            // You can add more rules here
        ];
    }
}