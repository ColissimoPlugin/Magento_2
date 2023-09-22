<?php


namespace LaPoste\Colissimo\Ui\Component\Create\Form\Prices;

use Magento\Framework\Data\OptionSourceInterface;
use LaPoste\Colissimo\Helper\CountryOffer;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\ProductMetadataInterface;

class OptionsArea implements OptionSourceInterface
{
    protected $countriesOffer;
    protected $context;
    protected $objectManager;
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    public function __construct(
        CountryOffer $helperCountryOffer,
        Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        ProductMetadataInterface $productMetadata
    ) {
        $this->countriesOffer = $helperCountryOffer->getCountriesPerZoneWithTrad();
        $this->context = $context;
        $this->objectManager = $objectManager;
        $this->productMetadata = $productMetadata;
    }

    public function toOptionArray()
    {
        $entityId = $this->context->getRequest()->getParam('entity_id');
        $url = $this->context->getRequest()->getUriString();
        $currentPrices = $this->objectManager->create(\LaPoste\Colissimo\Model\Prices::class)->load($entityId);
        $version240 = version_compare($this->productMetadata->getVersion(), '2.4.0', '>=');

        $options = [];
        $onListing = false;
        if ((empty($currentPrices) || empty($currentPrices->getMethod())) && strpos($url, 'laposte_colissimo/prices/new') === false) {
            $onListing = true;
        }

        $method = $currentPrices->getMethod();
        if (empty($method)) {
            $method = \LaPoste\Colissimo\Model\Carrier\Colissimo::CODE_SHIPPING_METHOD_DOMICILE_SS;
        }

        foreach ($this->countriesOffer as $zoneKey => $zone) {
            $optionsGroup = [];
            foreach ($zone['countries'] as $countryKey => $country) {
                if (empty($country[$method]) && !$onListing) {
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

        return $options;
    }
}
