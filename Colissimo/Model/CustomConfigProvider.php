<?php

namespace LaPoste\Colissimo\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use LaPoste\Colissimo\Helper\Data;

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
                'iconUrl'            => $colissimoIconUrl,
                'deliveryDate'       => (bool) $this->helperData->getAdvancedConfigValue('lpc_checkout/displayDeliveryDate'),
                'defaultCountryCode' => $this->scopeConfig->getValue(DirectoryHelper::XML_PATH_DEFAULT_COUNTRY, ScopeInterface::SCOPE_STORE),
            ],
        ];
    }
}
