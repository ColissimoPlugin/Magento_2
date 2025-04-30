<?php

namespace LaPoste\Colissimo\Block\Adminhtml\Order;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\PickUpPointApi;
use LaPoste\Colissimo\Helper\CountryOffer;
use LaPoste\Colissimo\Model\PricesRepository;
use LaPoste\Colissimo\Model\Carrier\Colissimo as ColissimoCarrier;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class PickupSelection extends Template
{
    protected $_template = 'LaPoste_Colissimo::order/pickupSelection.phtml';
    protected Data $helperData;
    protected PickUpPointApi $pickUpPointApi;
    protected StoreManagerInterface $storeManager;
    protected CountryOffer $countryOffer;
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    protected PricesRepository $pricesRepository;

    /**
     * @param Context               $context
     * @param Data                  $helperData
     * @param PickUpPointApi        $pickUpPointApi
     * @param StoreManagerInterface $storeManager
     * @param CountryOffer          $countryOffer
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PricesRepository      $pricesRepository
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        Data $helperData,
        PickUpPointApi $pickUpPointApi,
        StoreManagerInterface $storeManager,
        CountryOffer $countryOffer,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PricesRepository $pricesRepository,
        array $data = []
    ) {
        $this->helperData = $helperData;
        $this->pickUpPointApi = $pickUpPointApi;
        $this->storeManager = $storeManager;
        $this->countryOffer = $countryOffer;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->pricesRepository = $pricesRepository;
        parent::__construct($context, $data);
    }

    public function getPickupCarrierCode(): string
    {
        return ColissimoCarrier::CODE . '_' . ColissimoCarrier::CODE_SHIPPING_METHOD_RELAY;
    }

    protected function _prepareLayout()
    {
        $this->addChild(
            'lpc_choose_relay_button',
            Button::class,
            [
                'id'    => 'lpc_change_my_relay',
                'label' => __('Choose my relay'),
                'class' => 'action-secondary',
            ]
        );

        return parent::_prepareLayout();
    }

    public function getAjaxSetInformationRelayUrl()
    {
        return $this->getUrl('laposte_colissimo/relays/setRelayInformationAdmin');
    }

    public function lpcPickupType()
    {
        return $this->helperData->getConfigValue('lpc_advanced/lpc_pr_front/choosePRDisplayMode');
    }

    public function lpcGetAddressTextColor()
    {
        return $this->helperData->getConfigValue('lpc_advanced/lpc_pr_front/prAddressTextColor');
    }

    public function lpcGetListTextColor()
    {
        return $this->helperData->getConfigValue('lpc_advanced/lpc_pr_front/prListTextColor');
    }

    public function lpcGetFontWidgetPr()
    {
        $fontValue = $this->helperData->getConfigValue('lpc_advanced/lpc_pr_front/prDisplayFont');

        $fontNames = [
            'georgia'       => 'Georgia, serif',
            'palatino'      => '"Palatino Linotype", "Book Antiqua", Palatino, serif',
            'times'         => '"Times New Roman", Times, serif',
            'arial'         => 'Arial, Helvetica, sans-serif',
            'arialblack'    => '"Arial Black", Gadget, sans-serif',
            'comic'         => '"Comic Sans MS", cursive, sans-serif',
            'impact'        => 'Impact, Charcoal, sans-serif',
            'lucida'        => '"Lucida Sans Unicode", "Lucida Grande", sans-serif',
            'tahoma'        => 'Tahoma, Geneva, sans-serif',
            'trebuchet'     => '"Trebuchet MS", Helvetica, sans-serif',
            'verdana'       => 'Verdana, Geneva, sans-serif',
            'courier'       => '"Courier New", Courier, monospace',
            'lucidaconsole' => '"Lucida Console", Monaco, monospace',
        ];

        return $fontNames[$fontValue];
    }

    public function lpcGetCustomizeWidget()
    {
        return $this->helperData->getConfigValue('lpc_advanced/lpc_pr_front/prCustomizeWidget');
    }

    public function lpcAjaxUrlLoadRelaysList()
    {
        return $this->getUrl('laposte_colissimo/relays/loadRelaysAdmin');
    }

    public function lpcIsAutoRelay()
    {
        return $this->helperData->getConfigValue('lpc_advanced/lpc_pr_front/prAutoSelect');
    }

    public function lpcGetDefaultMobileDisplay()
    {
        return $this->helperData->getConfigValue('lpc_advanced/lpc_pr_front/prDefaultMobileDisplay');
    }

    public function lpcGetMaxRelayPoint()
    {
        return $this->helperData->getConfigValue('lpc_advanced/lpc_pr_front/maxRelayPoint');
    }

    public function getGoogleMapsUrl()
    {
        $apiKey = $this->helperData->getAdvancedConfigValue('lpc_pr_front/lpc_google_maps_api_key');

        return empty($apiKey) || $apiKey == '0' ? '' : 'https://maps.googleapis.com/maps/api/js?loading=async&libraries=marker&key=' . $apiKey;
    }

    public function lpcGetAveragePreparationDelay()
    {
        return $this->helperData->getAdvancedConfigValue('lpc_pr_front/averagePreparationDelay');
    }

    public function lpcGetRelayTypes()
    {
        return $this->helperData->getAdvancedConfigValue('lpc_pr_front/relayTypes');
    }

    public function lpcGetAuthenticationToken()
    {
        $authenticateResponse = $this->pickUpPointApi->authenticate();

        if ($authenticateResponse === false || empty($authenticateResponse->token)) {
            return false;
        } else {
            return $authenticateResponse->token;
        }
    }

    /**
     * Get list of enabled countries for relay method
     * @return string
     */
    public function getWidgetListCountry()
    {
        // Origin country
        $originCountryId = $this->helperData->getConfigValue('shipping/origin/country_id', $this->storeManager->getStore()->getId());

        // Get theoric countries available for relay method
        $countriesOfMethod = $this->countryOffer->getCountriesForMethod('pr', $originCountryId);

        // If always free, all countries of relay method are available in the widget
        if ('1' === $this->helperData->getConfigValue('carriers/lpc_group/pr_free')) {
            return implode(',', $countriesOfMethod);
        }

        // Get all areas configured for relay method
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $configPR = $this->pricesRepository->getList($searchCriteria, 'method', ColissimoCarrier::CODE_SHIPPING_METHOD_RELAY)->getItems();

        // Get the countries in both.
        $countriesTmp = [];
        foreach ($configPR as $oneConfig) {
            if ('pr' != $oneConfig->getMethod()) {
                continue;
            }
            $countriesZone = $this->countryOffer->getCountriesFromOneZone($oneConfig->getArea(), $originCountryId);
            foreach ($countriesZone as $oneCountry) {
                if (in_array($oneCountry, $countriesOfMethod)) {
                    $countriesTmp[$oneCountry] = 1;
                }
            }
        }
        $countries = array_keys($countriesTmp);

        return implode(',', $countries);
    }
}
