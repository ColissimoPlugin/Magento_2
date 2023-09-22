<?php

namespace LaPoste\Colissimo\Block;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Logger\Colissimo;
use LaPoste\Colissimo\Model\Carrier\Colissimo as ColissimoCarrier;
use LaPoste\Colissimo\Model\PickUpPointApi;
use LaPoste\Colissimo\Model\PricesRepository;
use LaPoste\Colissimo\Helper\CountryOffer;


class Selector extends Template
{
    protected $_template = "selector.phtml";
    protected $helperData;
    public $colissimoLogger;
    protected $_pickUpPointApi;
    protected $countryOffer;
    protected $pricesRepository;
    protected $searchCriteriaBuilder;
    protected $storeManager;

    public function __construct(
        Context $context,
        Data $helperData,
        Colissimo $logger,
        PickUpPointApi $pickUpPointApi,
        CountryOffer $countryOffer,
        PricesRepository $pricesRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->helperData = $helperData;
        $this->colissimoLogger = $logger;
        $this->_pickUpPointApi = $pickUpPointApi;
        $this->countryOffer = $countryOffer;
        $this->pricesRepository = $pricesRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    public function lpcAjaxUrlLoadRelaysList()
    {
        return $this->getUrl("lpc/relays/LoadRelays");
    }

    public function getAjaxSetInformationRelayUrl()
    {
        return $this->getUrl("lpc/relays/SetRelayInformationSession");
    }

    public function getGoogleMapsUrl()
    {
        $apiKey = $this->helperData->getAdvancedConfigValue('lpc_pr_front/lpc_google_maps_api_key');
        $urlGoogleMaps = empty($apiKey) || $apiKey == '0' ? '' : "https://maps.googleapis.com/maps/api/js?key=" . $apiKey;

        return $urlGoogleMaps;
    }

    public function lpcPrView()
    {
        return $this->helperData->getConfigValue("lpc_advanced/lpc_pr_front/choosePRDisplayMode");
    }

    public function lpcIsAutoRelay()
    {
        return $this->helperData->getConfigValue('lpc_advanced/lpc_pr_front/prAutoSelect');
    }

    public function lpcGetAuthenticationToken()
    {
        $authenticateResponse = $this->_pickUpPointApi->authenticate();

        if ($authenticateResponse === false || empty($authenticateResponse->token)) {
            return false;
        } else {
            return $authenticateResponse->token;
        }
    }

    public function lpcGetAveragePreparationDelay()
    {
        return $this->helperData->getConfigValue("lpc_advanced/lpc_pr_front/averagePreparationDelay");
    }

    public function lpcWidgetUrl()
    {
        return $this->helperData->getConfigValue("lpc_advanced/lpc_pr_front/prWidgetUrl");
    }

    public function lpcGetAddressTextColor()
    {
        return $this->helperData->getConfigValue("lpc_advanced/lpc_pr_front/prAddressTextColor");
    }

    public function lpcGetListTextColor()
    {
        return $this->helperData->getConfigValue("lpc_advanced/lpc_pr_front/prListTextColor");
    }

    public function lpcGetCustomizeWidget()
    {
        return $this->helperData->getConfigValue("lpc_advanced/lpc_pr_front/prCustomizeWidget");
    }

    public function lpcGetDefaultMobileDisplay()
    {
        return $this->helperData->getConfigValue("lpc_advanced/lpc_pr_front/prDefaultMobileDisplay");
    }

    public function lpcGetMaxRelayPoint()
    {
        return $this->helperData->getConfigValue("lpc_advanced/lpc_pr_front/maxRelayPoint");
    }

    public function lpcGetFontWidgetPr()
    {
        $fontValue = $this->helperData->getConfigValue("lpc_advanced/lpc_pr_front/prDisplayFont");

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
