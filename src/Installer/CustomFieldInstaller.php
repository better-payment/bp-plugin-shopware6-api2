<?php declare(strict_types=1);

namespace BetterPayment\Installer;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class CustomFieldInstaller
{
    // NOTE: DO NOT CONFUSE WORDS CUSTOM VS CUSTOMER :)
    // NOTE: IDs are hard coded to easily use upsert()

    private const CUSTOMER_SET_ID = '02d92016df1142c88130a82776c78988';
    private const CUSTOMER_SET = 'better_payment_customer';
    private const SETS = [
        [
            'id' => self::CUSTOMER_SET_ID,
            'name' => self::CUSTOMER_SET,
            'config' => [
                'label' => [
                    'en-GB' => 'Better Payment Customer',
                    'de-DE' => 'Better Payment Customer (DE)',
                ],
            ],
            'relations' => [
                [
                    'id' => '0fa91ce3e96a4bc2be4bc9ce752c3225',
                    'entityName' => 'customer',
                ],
            ],
            'customFields' => self::CUSTOMER_FIELDS
        ],
        // You can add more custom field sets here
    ];


    public const CUSTOMER_GENDER = 'better_payment_customer_gender';
    private const CUSTOMER_GENDER_ID = '082dce514dc84f3ea630f1fb0e8e112d';
    private const CUSTOMER_FIELDS = [
        [
            'id' => self::CUSTOMER_GENDER_ID,
            'name' => self::CUSTOMER_GENDER,
            'type' => CustomFieldTypes::SELECT,
            'config' => [
                'customFieldPosition' => 1,
                'customFieldType' => 'select',
                'componentName' => 'sw-single-select',
                'label' => [
                    'en-GB' => 'Gender',
                    'de-DE' => 'Gender (DE)',
                ],
                'helpText' => [
                    'en-GB' => 'This data is collected to be used for risk checks when enabled in PLugin config',
                    'de-DE' => 'This data is collected to be used for risk checks when enabled in PLugin config (DE)',
                ],
                'options' => [
                    [
                        'label' => [
                            'en-GB' => 'Male',
                            'de-DE' => 'Male (DE)',
                        ],
                        'value' => 'm'
                    ],
                    [
                        'label' => [
                            'en-GB' => 'Female',
                            'de-DE' => 'Female (DE)',
                        ],
                        'value' => 'f'
                    ],
                    [
                        'label' => [
                            'en-GB' => 'Diverse',
                            'de-DE' => 'Diverse (DE)',
                        ],
                        'value' => 'd'
                    ],
                ],
            ],
        ],
        // You can add more customer fields here
    ];

    private EntityRepositoryInterface $customFieldSetRepository;

    public function __construct(EntityRepositoryInterface $customFieldSetRepository)
    {
        $this->customFieldSetRepository = $customFieldSetRepository;
    }

    public function install(InstallContext $installContext): void
    {
        $this->customFieldSetRepository->upsert(self::SETS, $installContext->getContext());
    }

    // Other lifecycle methods can be implemented when required, now no need for them
}