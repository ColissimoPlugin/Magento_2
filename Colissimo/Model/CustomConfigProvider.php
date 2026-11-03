<?php

namespace LaPoste\Colissimo\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\Carrier\GenerateLabelPayload;

class CustomConfigProvider implements ConfigProviderInterface
{
    protected $assetRepository;
    protected $helperData;
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        Repository $assetRepository,
        Data $helperData,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->assetRepository = $assetRepository;
        $this->helperData = $helperData;
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig()
    {
        $displayLogo = $this->helperData->getConfigValue('carriers/lpc_group/display_logo');
        $colissimoIconUrl = '';
        if ('1' === $displayLogo) {
            $colissimoIconUrl = $this->assetRepository->getUrl('LaPoste_Colissimo::images/colissimo_icon.png');
        }

        return [
            'colissimo' => [
                'iconUrl'               => $colissimoIconUrl,
                'partnerLogos'          => empty($colissimoIconUrl) ? [] : $this->getPartnerLogos(),
                'postalNetworkEsPrefix' => array_map('strval', GenerateLabelPayload::SPECIAL_ES_POSTCODE),
                'deliveryDate'          => (bool) $this->helperData->getAdvancedConfigValue('lpc_checkout/displayDeliveryDate'),
                'defaultCountryCode'    => $this->scopeConfig->getValue(DirectoryHelper::XML_PATH_DEFAULT_COUNTRY, ScopeInterface::SCOPE_STORE),
            ],
        ];
    }

    protected function getPartnerLogos(): array
    {
        $dpd = ['file' => 'dpd.png', 'width' => 56];

        $logos = [
            'AU' => ['file' => 'australia_post.svg', 'width' => 100],
            'CA' => ['file' => 'canada_post.jpg', 'width' => 100],
            'CH' => ['file' => 'swiss_post.jpg', 'width' => 80],
            'DK' => $dpd,
            'EE' => $dpd,
            'GB' => ['file' => 'parcel_force.jpg', 'width' => 80],
            'IE' => $dpd,
            'NC' => ['file' => 'opt.png', 'width' => 100],
            'NL' => $dpd,
            'NO' => ['file' => 'postnord.svg', 'width' => 80],
            'PF' => ['file' => 'fare_rata.png', 'width' => 40],
            'PL' => $dpd,
            'PT' => $dpd,
            'SE' => ['file' => 'postnord.svg', 'width' => 80],
            'UA' => ['file' => 'ukrposhta.png', 'width' => 100],
        ];

        $localPostalServiceLogos = [
            'AT' => ['file' => 'post_ag.jpg', 'width' => 80],
            'BE' => ['file' => 'bpost.png', 'width' => 70],
            'DE' => ['file' => 'deutschpost.jpg', 'width' => 60],
            'ES' => ['file' => 'seur.png', 'width' => 80],
            'FI' => ['file' => 'posti.png', 'width' => 50],
            'IT' => ['file' => 'poste_italiane.jpg', 'width' => 100],
            'LU' => ['file' => 'deutschpost.jpg', 'width' => 60],
        ];
        $carrierLogos = ['IT' => ['file' => 'brt.png', 'width' => 52]];

        foreach ($localPostalServiceLogos as $countryCode => $logo) {
            $sendingService = $this->helperData->getConfigValue('carriers/lpc_group/domicileas_sendingservice_' . $countryCode);
            if ('partner' === $sendingService) {
                $logos[$countryCode] = $logo;
            } else {
                $logos[$countryCode] = $carrierLogos[$countryCode] ?? $dpd;
            }
        }

        foreach ($logos as $countryCode => $logo) {
            $logos[$countryCode] = [
                'url'   => $this->assetRepository->getUrl('LaPoste_Colissimo::images/partners/' . $logo['file']),
                'width' => $logo['width'],
            ];
        }

        return $logos;
    }
}
