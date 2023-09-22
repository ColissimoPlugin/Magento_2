<?php


namespace LaPoste\Colissimo\Block\Adminhtml\Prices;

use LaPoste\Colissimo\Helper\CountryOffer;
use Magento\Backend\Block\Template;
use LaPoste\Colissimo\Helper\Data;
use Magento\Framework\App\ProductMetadataInterface;

class Url extends Template
{
    protected $helperData;
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Data $helperData,
        CountryOffer $helperCountryOffer,
        ProductMetadataInterface $productMetadata,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->_objectManager = $objectManager;
        $this->helperData = $helperData;
        $this->countriesOffer = $helperCountryOffer->getCountriesPerZoneWithTrad();
        $this->productMetadata = $productMetadata;
    }

    public function getAllOptions()
    {
        $methods = \LaPoste\Colissimo\Model\Carrier\Colissimo::METHODS_CODES;
        $version240 = version_compare($this->productMetadata->getVersion(), '2.4.0', '>=');

        $allOptions = [];
        foreach ($methods as $method) {
            $options = [];
            foreach ($this->countriesOffer as $zoneKey => $zone) {
                $optionsGroup = [];
                foreach ($zone['countries'] as $countryKey => $country) {
                    if (empty($country[$method])) {
                        continue;
                    }

                    $label = empty($country['name']) ? $countryKey : $country['name'];
                    if (!$version240) {
                        $label = ' - ' . $label;
                    }
                    $optionsGroup[] = [
                        'label' => $label,
                        'value' => $countryKey,
                    ];
                }

                if (empty($optionsGroup)) {
                    continue;
                }

                $options[] = [
                    'label' => $zone['name'],
                    'value' => $zoneKey,
                ];

                if ($version240) {
                    $options[] = [
                        'label' => $zone['name'],
                        'value' => $optionsGroup,
                    ];
                } else {
                    $options = array_merge($options, $optionsGroup);
                }
            }
            $allOptions[$method] = $options;
        }

        return json_encode($allOptions);
    }

}
