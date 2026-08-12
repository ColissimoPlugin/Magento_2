<?php

namespace LaPoste\Colissimo\Block\Adminhtml\Simulation;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use Magento\Store\Model\ScopeInterface;

class Form extends Template
{
    const XML_PATH_ORIGIN_COUNTRY = 'shipping/origin/country_id';
    const XML_PATH_ORIGIN_POSTCODE = 'shipping/origin/postcode';

    /**
     * Delivery mode dropdown values, mapped to the web service parameters in
     * \LaPoste\Colissimo\Controller\Adminhtml\Simulation\Calculate.
     */
    const DELIVERY_MODE_HOME = 'home';
    const DELIVERY_MODE_HOME_SIGNATURE = 'home_signature';
    const DELIVERY_MODE_PICKUP_BPR = 'pickup_bpr';
    const DELIVERY_MODE_PICKUP_A2P = 'pickup_a2p';
    const DELIVERY_MODE_RETURN = 'return';

    /**
     * Rate options that can be added to the simulation (codeOption of the param_taxe table).
     */
    const OPTION_CODES = [
        'CONTRE_REMBOURSEMENT',
        'FRANC_TAXES_DROITS',
        'DELIVERY_DUTY_PAID',
        'NON_MECANISABLE',
        'VALEUR_ASSUREE',
        'MATIERES_DANGEREUSES',
        'AVIS_RECEPTION',
        'PARTENAIRE_POSTAL',
    ];

    /**
     * Expected "choix" value type per option, used to render the right control and
     * constrain client input. Codes not listed here fall back to a free text field.
     */
    const OPTION_TYPE_BOOLEAN = 'boolean';
    const OPTION_TYPE_AMOUNT = 'amount';
    const OPTION_TYPE_RECOMMENDATION = 'recommendation';

    const OPTION_VALUE_TYPES = [
        'CONTRE_REMBOURSEMENT' => self::OPTION_TYPE_BOOLEAN,
        'FRANC_TAXES_DROITS'   => self::OPTION_TYPE_BOOLEAN,
        'DELIVERY_DUTY_PAID'   => self::OPTION_TYPE_BOOLEAN,
        'NON_MECANISABLE'      => self::OPTION_TYPE_BOOLEAN,
        'MATIERES_DANGEREUSES' => self::OPTION_TYPE_BOOLEAN,
        'PARTENAIRE_POSTAL'    => self::OPTION_TYPE_BOOLEAN,
        'VALEUR_ASSUREE'       => self::OPTION_TYPE_AMOUNT,
        'AVIS_RECEPTION'       => self::OPTION_TYPE_RECOMMENDATION,
    ];

    protected CountryCollectionFactory $countryCollectionFactory;

    public function __construct(
        Context $context,
        CountryCollectionFactory $countryCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->countryCollectionFactory = $countryCollectionFactory;
    }

    /**
     * @return string admin URL of the "calculateCost" AJAX endpoint
     */
    public function getCalculateUrl(): string
    {
        return $this->getUrl('laposte_colissimo/simulation/calculate');
    }

    /**
     * @return array list of countries as [['value' => 'FR', 'label' => 'France'], ...]
     */
    public function getCountriesOptions(): array
    {
        return $this->countryCollectionFactory->create()
                                              ->loadData()
                                              ->toOptionArray(false);
    }

    /**
     * Sender country pre-filled from the store shipping origin.
     *
     * @return string
     */
    public function getOriginCountry(): string
    {
        return (string) $this->_scopeConfig->getValue(
            self::XML_PATH_ORIGIN_COUNTRY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Sender zip code pre-filled from the store shipping origin.
     *
     * @return string
     */
    public function getOriginPostcode(): string
    {
        return (string) $this->_scopeConfig->getValue(
            self::XML_PATH_ORIGIN_POSTCODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Single delivery mode dropdown replacing home delivery / signature / point type.
     *
     * @return array
     */
    public function getDeliveryModeOptions(): array
    {
        return [
            ['value' => self::DELIVERY_MODE_HOME, 'label' => __('Home without signature')],
            ['value' => self::DELIVERY_MODE_HOME_SIGNATURE, 'label' => __('Home with signature')],
            ['value' => self::DELIVERY_MODE_PICKUP_BPR, 'label' => __('Pickup point at post office')],
            ['value' => self::DELIVERY_MODE_PICKUP_A2P, 'label' => __('Pickup point (others)')],
            ['value' => self::DELIVERY_MODE_RETURN, 'label' => __('Return parcel')],
        ];
    }

    /**
     * @return array price type options (general / discounted)
     */
    public function getTariffTypeOptions(): array
    {
        return [
            ['value' => '0', 'label' => __('General price')],
            ['value' => '1', 'label' => __('Discounted price')],
        ];
    }

    /**
     * Rate options offered in the dropdown, as value (codeOption) / human readable label pairs.
     *
     * @return array
     */
    public function getOptionCodes(): array
    {
        $labels = [
            'CONTRE_REMBOURSEMENT' => __('Cash on delivery'),
            'FRANC_TAXES_DROITS'   => __('Franked duties and taxes'),
            'DELIVERY_DUTY_PAID'   => __('Delivery Duty Paid'),
            'NON_MECANISABLE'      => __('Non-machinable parcel'),
            'VALEUR_ASSUREE'       => __('Insured value'),
            'MATIERES_DANGEREUSES' => __('Hazardous materials'),
            'AVIS_RECEPTION'       => __('Registered mail level'),
            'PARTENAIRE_POSTAL'    => __('Postal partner'),
        ];

        $options = [];
        foreach (self::OPTION_CODES as $code) {
            $options[] = [
                'value' => $code,
                'label' => $labels[$code] ?? $code,
            ];
        }

        return $options;
    }

    /**
     * @return array<string, string> map of option code => expected value type
     */
    public function getOptionValueTypes(): array
    {
        return self::OPTION_VALUE_TYPES;
    }
}
