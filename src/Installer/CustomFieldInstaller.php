<?php declare(strict_types=1);

namespace BetterPayment\Installer;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
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
                    'de-DE' => 'Better Payment Kunde',
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
    public const CUSTOMER_GENDER_ID = '082dce514dc84f3ea630f1fb0e8e112d';
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
                    'de-DE' => 'Geschlecht',
                ],
                'helpText' => [
                    'en-GB' => 'This data is collected to be used for risk checks when enabled in the plugin configurations.',
                    'de-DE' => 'Diese Daten werden gesammelt, um für Risikoprüfungen verwendet zu werden, wenn sie in Plugin-Konfigurationen aktiviert sind.',
                ],
                'options' => [
                    [
                        'label' => [
                            'en-GB' => 'Male',
                            'de-DE' => 'Männlich',
                        ],
                        'value' => 'm'
                    ],
                    [
                        'label' => [
                            'en-GB' => 'Female',
                            'de-DE' => 'Weiblich',
                        ],
                        'value' => 'f'
                    ],
                    [
                        'label' => [
                            'en-GB' => 'Diverse',
                            'de-DE' => 'Vielfältig',
                        ],
                        'value' => 'd'
                    ],
                ],
            ],
        ],
        // You can add more customer fields here
    ];

    private EntityRepository $customFieldSetRepository;

    public function __construct(EntityRepository $customFieldSetRepository)
    {
        $this->customFieldSetRepository = $customFieldSetRepository;
    }

    public function install(InstallContext $installContext): void
    {
        $this->customFieldSetRepository->upsert(self::SETS, $installContext->getContext());
    }

    // Other lifecycle methods can be implemented when required, now no need for them
}